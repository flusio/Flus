<?php

namespace App\search_engine\Query;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
enum TokenType: string
{
    case And = 'and';
    case CloseBracket = 'close bracket';
    case EndOfQuery = 'end of query';
    case Not = 'not';
    case OpenBracket = 'open bracket';
    case Or = 'or';
    case Qualifier = 'qualifier';
    case Tag = 'tag';
    case Text = 'text';
}
