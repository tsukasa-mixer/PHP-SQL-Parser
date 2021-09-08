<?php

namespace PHPSQLParser\builders;

class AlterStatementBuilder implements Builder
{
    public function build(array $parsed)
    {
        $alter = $parsed['ALTER'];
        return $this->buildAlter($alter);
    }

    private function buildAlter($parsed)
    {
        $builder = new AlterBuilder();
        return $builder->build($parsed);
    }

    protected function buildSubTree($parsed)
    {
        $builder = new SubTreeBuilder();
        return $builder->build($parsed);
    }
}
