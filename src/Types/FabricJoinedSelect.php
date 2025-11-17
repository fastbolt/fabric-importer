<?php

namespace Fastbolt\FabricImporter\Types;

class FabricJoinedSelect
{
    /**
     * @param string      $field           The field on the joined table
     * @param string|null $targetField     The field on the local/your table
     */
    public function __construct(
        private string $field,
        private ?string $targetField = null
    ) {
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * The field to which the data will be saved. Is also used as alias, as long as i don't notice that making any problems.
     *
     * @return string
     */
    public function getTargetField(): string
    {
        return $this->targetField ?? $this->field;
    }
}
