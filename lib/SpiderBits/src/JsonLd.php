<?php

namespace SpiderBits;

/**
 * This class helps to deal with json+ld contents. Note this is NOT a fully
 * operational json+ld library.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class JsonLd
{
    public function __construct(
        /** @var mixed[] $json */
        private array $json
    ) {
    }

    public function duration(): string
    {
        return $this->durationFromNode($this->json);
    }

    /**
     * @param mixed[] $node
     */
    private function durationFromNode(array $node): string
    {
        $duration_text = $node['duration'] ?? null;

        if (is_string($duration_text)) {
            return $duration_text;
        }

        $mainEntity = $node['mainEntity'] ?? null;

        if (is_array($mainEntity)) {
            return self::durationFromNode($mainEntity);
        }

        foreach ($this->graph($node) as $child_node) {
            if (!is_array($child_node)) {
                continue;
            }

            $duration = $this->durationFromNode($child_node);

            if ($duration) {
                return $duration;
            }
        }

        return '';
    }

    /**
     * @param mixed[] $node
     * @return mixed[]
     */
    private function graph(array $node): array
    {
        $graph = $node['@graph'] ?? null;

        if (!is_array($graph)) {
            return [];
        }

        return $graph;
    }
}
