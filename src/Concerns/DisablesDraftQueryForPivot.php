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
        return Publisher::withoutDraftContent(fn () => parent::addWhereConstraints());
    }

    /**
     * Set the join clause for the relation query.
     *
     * The "child" method is for supporting older Laravel versions that call
     * `performJoin($query)`, while newer versions call `addJoinClause($query)`.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return $this
     */
    protected function performJoin($query = null)
    {
        return Publisher::withoutDraftContent(fn () => parent::performJoin($query));
    }
}
