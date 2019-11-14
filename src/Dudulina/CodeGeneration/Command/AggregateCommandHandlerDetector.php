<?php
/******************************************************************************
 * Copyright (c) 2016 Constantin Galbenu <gica.galbenu@gmail.com>             *
 ******************************************************************************/

namespace Dudulina\CodeGeneration\Command;


use Gica\CodeAnalysis\MethodListenerDiscovery\MessageClassDetector;
use Gica\CodeAnalysis\Shared\ClassComparison\SubclassComparator;
use Dudulina\Command;

class AggregateCommandHandlerDetector implements MessageClassDetector
{
    public function isMessageClass(\ReflectionClass $typeHintedClass):bool
    {
        return (new SubclassComparator())->isASubClassButNoSameClass($typeHintedClass->name, Command::class);
    }

    public function isMethodAccepted(\ReflectionMethod $reflectionMethod):bool
    {
        return 0 === stripos($reflectionMethod->name, 'handle') ||
            false !== stripos($reflectionMethod->getDocComment(), '@AggregateCommandHandler');
    }
}