<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting;

/**
 * Container for report data grouped by a specific dimension key.
 *
 * Each group is identified by a name (e.g. hostgroup name) and contains
 * its own {@link ReportData} instance with the rows belonging to that group.
 */
class GroupedReportData
{
    /** @var string The config key used for grouping (e.g. 'hostgroup') */
    protected $groupBy;

    /** @var array<string, ReportData> Map of group name => ReportData */
    protected $groups = [];

    /** @var ReportData|null Grand total across all groups */
    protected $grandTotal = null;

    public function __construct(string $groupBy)
    {
        $this->groupBy = $groupBy;
    }

    /**
     * Get the key that data is grouped by
     *
     * @return string
     */
    public function getGroupBy(): string
    {
        return $this->groupBy;
    }

    /**
     * Add a named group with its data
     *
     * @param string     $name  Human-readable group identifier (e.g. the hostgroup name)
     * @param ReportData $data  Report data belonging to this group
     *
     * @return $this
     */
    public function addGroup(string $name, ReportData $data): self
    {
        $this->groups[$name] = $data;

        return $this;
    }

    /**
     * Get all groups
     *
     * @return array<string, ReportData>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Get whether any groups have been added
     *
     * @return bool
     */
    public function hasGroups(): bool
    {
        return ! empty($this->groups);
    }

    /**
     * Set the grand total data across all groups
     *
     * @param ReportData $data
     *
     * @return $this
     */
    public function setGrandTotal(ReportData $data): self
    {
        $this->grandTotal = $data;

        return $this;
    }

    /**
     * Get the grand total data across all groups, if set
     *
     * @return ReportData|null
     */
    public function getGrandTotal(): ?ReportData
    {
        return $this->grandTotal;
    }
}
