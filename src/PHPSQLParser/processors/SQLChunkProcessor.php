<?php
/**
 * SQLChunkProcessor.php
 *
 * This file implements the processor for the SQL chunks.
 *
 * PHP version 5
 *
 * LICENSE:
 * Copyright (c) 2010-2014 Justin Swanhart and André Rothe
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author    André Rothe <andre.rothe@phosco.info>
 * @copyright 2010-2014 Justin Swanhart and André Rothe
 * @license   http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @version   SVN: $Id$
 *
 */

namespace PHPSQLParser\processors;

/**
 * This class processes the SQL chunks.
 *
 * @author  André Rothe <andre.rothe@phosco.info>
 * @license http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 *
 */
class SQLChunkProcessor extends AbstractProcessor
{

    public function process($out)
    {
        if (!$out) {
            return false;
        }
        if (!empty($out['BRACKET'])) {
            // TODO: this field should be a global STATEMENT field within the output
            // we could add all other categories as sub_tree, it could also work with multipe UNIONs
            $processedBracket = $this->getBracketProcessor()->process($out['BRACKET']);
            $remainingExpressions = $processedBracket[0]['remaining_expressions'];

            unset($processedBracket[0]['remaining_expressions']);

            if (!empty($remainingExpressions)) {
                foreach ($remainingExpressions as $key => $expression) {
                    $processedBracket[][$key] = $expression;
                }
            }

            $out['BRACKET'] = $processedBracket;
        }
        if (!empty($out['CREATE'])) {
            $out['CREATE'] = $this->getCreateProcessor()->process($out['CREATE']);
        }
        if (!empty($out['TABLE'])) {
            $out['TABLE'] = $this->getTableProcessor()->process($out['TABLE']);
            $this->moveLIKE($out);
        }
        if (!empty($out['INDEX'])) {
            $out['INDEX'] = $this->getIndexProcessor()->process($out['INDEX']);
        }
        if (!empty($out['EXPLAIN'])) {
            $out['EXPLAIN'] = $this->getExplainProcessor()->process($out['EXPLAIN'], array_keys($out));
        }
        if (!empty($out['DESCRIBE'])) {
            $out['DESCRIBE'] = $this->getDescribeProcessor()->process($out['DESCRIBE'], array_keys($out));
        }
        if (!empty($out['DESC'])) {
            $out['DESC'] = $this->getDescProcessor()->process($out['DESC'], array_keys($out));
        }
        if (!empty($out['SELECT'])) {
            $out['SELECT'] = $this->getSelectProcessor()->process($out['SELECT']);
        }
        if (!empty($out['FROM'])) {
            $out['FROM'] = $this->getFromProcessor()->process($out['FROM']);
        }
        if (!empty($out['USING'])) {
            $out['USING'] = $this->getUsingProcessor()->process($out['USING']);
        }
        if (!empty($out['UPDATE'])) {
            $out['UPDATE'] = $this->getUpdateProcessor()->process($out['UPDATE']);
        }
        if (!empty($out['GROUP'])) {
            // set empty array if we have partial SQL statement
            $out['GROUP'] = $this->getGroupByProcessor()
                ->process($out['GROUP'], isset($out['SELECT']) ? $out['SELECT'] : array());
        }
        if (!empty($out['ORDER'])) {
            // set empty array if we have partial SQL statement
            $out['ORDER'] = $this->getOrderByProcessor()
                ->process($out['ORDER'], isset($out['SELECT']) ? $out['SELECT'] : array());
        }
        if (!empty($out['LIMIT'])) {
            $out['LIMIT'] = $this->getLimitProcessor()->process($out['LIMIT']);
        }
        if (!empty($out['WHERE'])) {
            $out['WHERE'] = $this->getWhereProcessor()->process($out['WHERE']);
        }
        if (!empty($out['HAVING'])) {
            $out['HAVING'] = $this->getHavingProcessor()
                ->process($out['HAVING'], isset($out['SELECT']) ? $out['SELECT'] : array());
        }
        if (!empty($out['SET'])) {
            $out['SET'] = $this->getSetProcessor()->process($out['SET'], isset($out['UPDATE']));
        }
        if (!empty($out['DUPLICATE'])) {
            $out['ON DUPLICATE KEY UPDATE'] = $this->getDuplicateProcessor()->process($out['DUPLICATE']);
            unset($out['DUPLICATE']);
        }
        if (!empty($out['INSERT'])) {
            $out = $this->getInsertProcessor()->process($out);
        }
        if (!empty($out['REPLACE'])) {
            $out = $this->getReplaceProcessor()->process($out);
        }
        if (!empty($out['DELETE'])) {
            $out = $this->getDeleteProcessor()->process($out);
        }
        if (!empty($out['VALUES'])) {
            $out = $this->getValuesProcessor()->process($out);
        }
        if (!empty($out['INTO'])) {
            $out = $this->getIntoProcessor()->process($out);
        }
        if (!empty($out['DROP'])) {
            $out['DROP'] = $this->getDropProcessor()->process($out['DROP']);
        }
        if (!empty($out['RENAME'])) {
            $out['RENAME'] = $this->getRenameProcessor()->process($out['RENAME']);
        }
        if (!empty($out['SHOW'])) {
            $out['SHOW'] = $this->getShowProcessor()->process($out['SHOW']);
        }
        if (!empty($out['OPTIONS'])) {
            $out['OPTIONS'] = $this->getOptionsProcessor()->process($out['OPTIONS']);
        }
        if (!empty($out['WITH'])) {
            $out['WITH'] = $this->getWithProcessor()->process($out['WITH']);
        }

        return $out;
    }

    protected function moveLIKE(&$out)
    {
        if (!isset($out['TABLE']['like'])) {
            return;
        }
        $out = $this->arrayInsertAfter($out, 'TABLE', array('LIKE' => $out['TABLE']['like']));
        unset($out['TABLE']['like']);
    }

    protected function getBracketProcessor()
    {
        return $this->options->getProcessor(BracketProcessor::class);
    }

    protected function getCreateProcessor()
    {
        return $this->options->getProcessor(CreateProcessor::class);
    }

    protected function getTableProcessor()
    {
        return $this->options->getProcessor(TableProcessor::class);
    }

    protected function getIndexProcessor()
    {
        return $this->options->getProcessor(IndexProcessor::class);
    }

    protected function getExplainProcessor()
    {
        return $this->options->getProcessor(ExplainProcessor::class);
    }

    protected function getDescribeProcessor()
    {
        return $this->options->getProcessor(DescribeProcessor::class);
    }

    protected function getDescProcessor()
    {
        return $this->options->getProcessor(DescProcessor::class);
    }

    protected function getSelectProcessor()
    {
        return $this->options->getProcessor(SelectProcessor::class);
    }

    protected function getFromProcessor()
    {
        return $this->options->getProcessor(FromProcessor::class);
    }

    protected function getUsingProcessor()
    {
        return $this->options->getProcessor(UsingProcessor::class);
    }

    protected function getUpdateProcessor()
    {
        return $this->options->getProcessor(UpdateProcessor::class);
    }

    protected function getGroupByProcessor()
    {
        return $this->options->getProcessor(GroupByProcessor::class);
    }

    protected function getOrderByProcessor()
    {
        return $this->options->getProcessor(OrderByProcessor::class);
    }

    protected function getLimitProcessor()
    {
        return $this->options->getProcessor(LimitProcessor::class);
    }

    protected function getWhereProcessor()
    {
        return $this->options->getProcessor(WhereProcessor::class);
    }

    protected function getHavingProcessor()
    {
        return $this->options->getProcessor(HavingProcessor::class);
    }

    protected function getSetProcessor()
    {
        return $this->options->getProcessor(SetProcessor::class);
    }

    protected function getDuplicateProcessor()
    {
        return $this->options->getProcessor(DuplicateProcessor::class);
    }

    protected function getInsertProcessor()
    {
        return $this->options->getProcessor(InsertProcessor::class);
    }

    protected function getReplaceProcessor()
    {
        return $this->options->getProcessor(ReplaceProcessor::class);
    }

    protected function getDeleteProcessor()
    {
        return $this->options->getProcessor(DeleteProcessor::class);
    }

    protected function getValuesProcessor()
    {
        return $this->options->getProcessor(ValuesProcessor::class);
    }

    protected function getIntoProcessor()
    {
        return $this->options->getProcessor(IntoProcessor::class);
    }

    protected function getDropProcessor()
    {
        return $this->options->getProcessor(DropProcessor::class);
    }

    protected function getRenameProcessor()
    {
        return $this->options->getProcessor(RenameProcessor::class);
    }

    protected function getShowProcessor()
    {
        return $this->options->getProcessor(ShowProcessor::class);
    }

    protected function getOptionsProcessor()
    {
        return $this->options->getProcessor(OptionsProcessor::class);
    }

    protected function getWithProcessor()
    {
        return $this->options->getProcessor(WithProcessor::class);
    }
}
