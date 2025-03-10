<?php

namespace Plank\Publisher\Concerns;

use Plank\Publisher\Facades\Publisher;

trait DisablesDraftQueryForPivot
{
    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        /**
         * Pivotted relationships use the related model's eloquent builder to
         * query the pivot table. In the case you are querying for a Publishable
         * model – while draft content is allowed – you don't want the draft
         * queries to be applied to the pivot table constraints.
         */
        Publisher::withoutDraftContent(function () {
            $this->query->where(
                $this->getQualifiedForeignPivotKeyName(), '=', $this->parent->{$this->parentKey}
            );
        });

        return $this;
    }
}
