<?php

declare(strict_types=1);

namespace AsyncAws\CodeGenerator\Generator\RequestSerializer;

use AsyncAws\CodeGenerator\Definition\ListShape;
use AsyncAws\CodeGenerator\Definition\MapShape;
use AsyncAws\CodeGenerator\Definition\Member;
use AsyncAws\CodeGenerator\Definition\Operation;
use AsyncAws\CodeGenerator\Definition\Shape;
use AsyncAws\CodeGenerator\Definition\StructureMember;
use AsyncAws\CodeGenerator\Definition\StructureShape;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @internal
 */
class RestXmlSerializer implements Serializer
{
    public function getContentType(): string
    {
        return 'application/xml';
    }

    public function generateRequestBody(Operation $operation, StructureShape $shape): array
    {
        if (null === $payloadProperty = $shape->getPayload()) {
            return ['$body = "";', false];
        }

        $member = $shape->getMember($payloadProperty);
        if ($member->isStreaming()) {
            return ['$body = $this->' . $payloadProperty . ' ?? "";', false];
        }

        return ['
            $document = new \DOMDocument(\'1.0\', \'UTF-8\');
            $document->formatOutput = false;
            $this->requestBody($document, $document);
            $body = $document->hasChildNodes() ? $document->saveXML() : "";
        ',  true, ['node' => '\DomNode']];
    }

    public function generateRequestBuilder(StructureShape $shape): array
    {
        $body = implode("\n", array_map(function (StructureMember $member) {
            if (null !== $member->getLocation()) {
                return '';
            }

            $shape = $member->getShape();
            if ($member->isRequired() || $shape instanceof ListShape || $shape instanceof MapShape) {
                $body = 'MEMBER_CODE';
                $inputElement = '$this->' . $member->getName();
            } else {
                $body = 'if (null !== $v = INPUT_NAME) {
                    MEMBER_CODE
                }';
                $inputElement = '$v';
            }

            return strtr($body, [
                'INPUT_NAME' => '$this->' . $member->getName(),
                'MEMBER_CODE' => $this->dumpXmlShape($member, $member->getShape(), '$node', $inputElement),
            ]);
        }, $shape->getMembers()));

        return ['void', $body, ['node' => '\DomElement', 'document' => '\DomDocument']];
    }

    private function dumpXmlShape(Member $member, Shape $shape, string $output, string $input): string
    {
        switch (true) {
            case $shape instanceof StructureShape:
                return $this->dumpXmlShapeStructure($member, $shape, $output, $input);
            case $shape instanceof ListShape:
                return $this->dumpXmlShapeList($member, $shape, $output, $input);
        }

        switch ($shape->getType()) {
            case 'blob':
            case 'string':
                return $this->dumpXmlShapeString($member, $output, $input);
            case 'boolean':
                return $this->dumpXmlShapeBoolean($member, $output, $input);
        }

        throw new \RuntimeException(sprintf('Type %s is not yet implemented', $shape->getType()));
    }

    private function dumpXmlShapeStructure(Member $member, StructureShape $shape, string $output, string $input): string
    {
        $xmlnsValue = '';
        $xmlnsAttribute = '';
        if ($member instanceof StructureMember && null !== $ns = $member->getXmlNamespaceUri()) {
            $xmlnsValue = $ns;
            $xmlnsAttribute = (null !== $prefix = $member->getXmlNamespacePrefix()) ? 'xmlns:' . $prefix : 'xmlns';
        } elseif (null !== $ns = $shape->getXmlNamespaceUri()) {
            $xmlnsValue = $ns;
            $xmlnsAttribute = (null !== $prefix = $shape->getXmlNamespacePrefix()) ? 'xmlns:' . $prefix : 'xmlns';
        }

        return strtr('
            OUTPUT->appendChild($child = $document->createElement(NODE_NAME));
            SET_XMLNS_CODE
            PSALM
            INPUT->requestBody($child, $document);
        ', [
            'OUTPUT' => $output,
            'NODE_NAME' => \var_export($member->getLocationName() ?? ($member instanceof StructureMember ? $member->getName() : 'member'), true),
            'SET_XMLNS_CODE' => $xmlnsValue ? strtr('$child->setAttribute(NS_ATTRIBUTE, NS_VALUE);', ['NS_ATTRIBUTE' => \var_export($xmlnsAttribute, true), 'NS_VALUE' => \var_export($xmlnsValue, true)]) : '',
            'PSALM' => '$v' === $input ? '' : '/** @psalm-suppress PossiblyNullReference */',
            'INPUT' => $input,
        ]);
    }

    private function dumpXmlShapeList(Member $member, ListShape $shape, string $output, string $input): string
    {
        if ($shape->isFlattened()) {
            return strtr('
                foreach (INPUT as $item) {
                    MEMBER_CODE
                }
            ', [
                'INPUT' => $input,
                'MEMBER_CODE' => $this->dumpXmlShape($member, $shape->getMember()->getShape(), $output, '$item'),
            ]);
        }

        return strtr('
                OUTPUT->appendChild($nodeList = $document->createElement(NODE_NAME));
                foreach (INPUT as $item) {
                    MEMBER_CODE
                }
            ', [
            'OUTPUT' => $output,
            'NODE_NAME' => \var_export($member->getLocationName() ?? ($member instanceof StructureMember ? $member->getName() : 'member'), true),
            'INPUT' => $input,
            'MEMBER_CODE' => $this->dumpXmlShape($shape->getMember(), $shape->getMember()->getShape(), '$nodeList', '$item'),
        ]);
    }

    private function dumpXmlShapeString(Member $member, string $output, string $input): string
    {
        if ($member instanceof StructureMember && $member->isXmlAttribute()) {
            $body = 'OUTPUT->setAttribute(NODE_NAME, INPUT);';
        } else {
            $body = 'OUTPUT->appendChild($document->createElement(NODE_NAME, INPUT));';
        }

        return \strtr($body, [
            'INPUT' => $input,
            'OUTPUT' => $output,
            'NODE_NAME' => \var_export($member->getLocationName() ?? ($member instanceof StructureMember ? $member->getName() : 'member'), true),
        ]);
    }

    private function dumpXmlShapeBoolean(Member $member, string $output, string $input): string
    {
        if ($member instanceof StructureMember && $member->isXmlAttribute()) {
            throw new \InvalidArgumentException('Boolean Shape with xmlAttribute is not yet implemented.');
        }

        $body = 'OUTPUT->appendChild($document->createElement(NODE_NAME, INPUT ? \'true\' : \'false\'));';

        return \strtr($body, [
            'INPUT' => $input,
            'OUTPUT' => $output,
            'NODE_NAME' => \var_export($member->getLocationName() ?? ($member instanceof StructureMember ? $member->getName() : 'member'), true),
        ]);
    }
}