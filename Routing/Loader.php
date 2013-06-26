<?php

namespace Egzakt\SystemBundle\Routing;

use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouteCollection;

use JMS\I18nRoutingBundle\Router\I18nLoader as BaseLoader;

/**
 * This loader expand the sectionPath of each mapping entries
 */
class Loader extends BaseLoader
{
    /**
     * @var Connection
     */
    protected $databaseConnection;

    /**
     * @var array
     */
    protected $mappings;

    /**
     * @var array
     */
    protected $routesToRemove;

    /**
     * @inheritdoc
     */
    public function load(RouteCollection $collection)
    {
        $collection = parent::load($collection);

        $this->mappings = $this->databaseConnection->fetchAll($this->getMappingSqlQuery());

        foreach ($this->mappings as $mapping) {
            $this->generate($mapping, $collection);
        }

        $this->routesToRemove = array_unique($this->routesToRemove);
        foreach ($this->routesToRemove as $routeToRemove) {
            $collection->remove($routeToRemove);
        }

        return $collection;
    }

    /**
     * @param array $mapping
     * @param RouteCollection $collection
     * @param string $name
     *
     * @return mixed
     *
     * @throws RouteNotFoundException
     */
    protected function generate($mapping, $collection, $name = '')
    {
        $sourceName = $mapping['locale'] . self::ROUTING_PREFIX . $mapping['target'];

        if ($sourceRoute = $collection->get($sourceName)) {
            $route = clone $sourceRoute;
        } else {
            throw new RouteNotFoundException(sprintf('Unable to generate a mapping using named route "%s" as such route does not exist.', $sourceName));
        }

        // Validate parents hierarchy
        if (false == $this->validateParents($mapping['section_id'], $mapping['locale'])) {
            return false;
        }

        // No route name specified, fallback to the auto generated one
        if (false == $name) {
            $name = $mapping['locale'] . self::ROUTING_PREFIX . 'section_id_' . $mapping['section_id'];
        }

        if (false == $sourceRoute->getOption('keep_on_mapping')) {
            $this->routesToRemove[] = $sourceName;
        }

        // The section does have any active text, using the first child as the generation starting point
        if (false == $mapping['has_text'] && $mapping['has_children']) {
            return $this->generate($this->findFirstChild($mapping['section_id'], $mapping['locale']), $collection, $name);
        }

        // expanding section paths
        $sectionsPath = $this->computeParentSlugs($mapping['section_id'], $mapping['locale']);

        if ($mapping['app_prefix']) {
            $sectionsPath = $mapping['app_prefix'] . '/' . $sectionsPath;
        }

        $expandedPattern = preg_replace('/{(sectionsPath|sections_path)}/', $sectionsPath, $route->getPattern());
        $route->setPattern($expandedPattern);

        // additionals parameters
        $route->setDefault('_sectionId', $mapping['section_id']);
        $route->setDefault('_appId', $mapping['app_id']);
        $route->setDefault('_appPrefix', $mapping['app_prefix']);
        $route->setDefault('_sectionSlug', $mapping['slug']);
        $route->setDefault('_sectionsPath', $sectionsPath);
        $route->setDefault('_mappedRouteName', $sourceName);

        // adding the route to the main collection
        $collection->add($name, $route);
    }

    /**
     * This compute the full slug-path of a given section using the mapping array.
     *
     * Ex: "/our-company/mission/staff"
     *
     * @param $sectionId
     * @param $locale
     *
     * @return string
     */
    protected function computeParentSlugs($sectionId, $locale)
    {
        foreach ($this->mappings as $mapping) {
            if ($sectionId === $mapping['section_id'] && $locale === $mapping['locale']) {
                $slug = $mapping['slug'];
                if ($mapping['parent_id']) {
                    return $this->computeParentSlugs($mapping['parent_id'], $locale) . '/' . $slug;
                }
                return $slug;
            }
        }
    }

    /**
     * Find a mapping by sectionId.
     *
     * @param $sectionId
     * @param $locale
     *
     * @return array
     */
    protected function find($sectionId, $locale)
    {
        foreach ($this->mappings as $mapping) {
            if ($sectionId === $mapping['section_id'] && $locale === $mapping['locale']) {
                return $mapping;
            }
        }
    }

    /**
     * Find the first child of a given section using the mapping array.
     *
     * @param $sectionId
     * @param $locale
     *
     * @return array
     */
    protected function findFirstChild($sectionId, $locale)
    {
        foreach ($this->mappings as $mapping) {
            if ($sectionId === $mapping['parent_id'] && $locale === $mapping['locale']) {
                return $mapping;
            }
        }
    }

    /**
     * Validate that each parents from sectionId is present in the hierarchy, if a parent is not
     * present this mean that the section is not active.
     *
     * @param $sectionId
     * @param $locale
     *
     * @return array
     */
    protected function validateParents($sectionId, $locale)
    {
        $mapping = $this->find($sectionId, $locale);

        while ($mapping['parent_id']) {
            if (!$mapping = $this->find($mapping['parent_id'], $locale)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The main query used to fetch the mappings.
     * This query is in raw SQL for performance reason.
     *
     * @return string
     */
    protected function getMappingSqlQuery()
    {
        return '
            SELECT m.target, s.id as section_id, s.parent_id, st.locale, st.slug, a.id as app_id, a.prefix as app_prefix, (
                SELECT COUNT(t.id) FROM text t
                INNER JOIN text_translation tt ON tt.translatable_id = t.id
                WHERE t.section_id = s.id
                AND tt.active = 1
            ) AS has_text, (
                SELECT COUNT(ss.id)
                FROM section ss
                INNER JOIN section_translation sst ON sst.translatable_id = ss.id
                WHERE ss.parent_id = s.id
                AND sst.active = 1
            ) AS has_children
            FROM mapping m
            INNER JOIN section s ON s.id = m.section_id
            INNER JOIN section_translation st ON st.translatable_id = s.id
            INNER JOIN app a ON a.id = s.app_id
            WHERE m.app_id <> 1
            AND s.app_id <> 1
            AND m.type = "route"
            AND st.active = 1
            ORDER BY m.app_id, s.parent_id, s.ordering
        ';
    }

    /**
     * @param Connection $databaseConnection
     */
    public function setDatabaseConnection($databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

}