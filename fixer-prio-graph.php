<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\GraphViz\GraphViz;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\Tests\AutoReview\FixerFactoryTest;

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/vendor/autoload.php';

if (!$fixerFactoryFile = $loader->findFile(FixerFactory::class)) {
    die('PHP-CS-Fixer not found!');
}

// Manually add php-cs-fixer's tests to the autoloader, as composer doesn't add
// autoload-dev. It also doesn't include the tests itself when using packagist,
// so we just use a git clone. (See composer.json)
$fixerTestDir = dirname($fixerFactoryFile, 2) . '/tests';
$loader->addPsr4('PhpCsFixer\\Tests\\', $fixerTestDir);

// Et voila, we can use a(n internal) test-class
$factory = new FixerFactoryTest();

/** @var Vertex[] $vertices */
$vertices = [];
$graph    = new Graph();

// Left to right provides a more readable graph in our case than top-down
$graph->setAttribute('graphviz.graph.rankdir', 'LR');

// Ratio: height / width, stretch a little for readability
$graph->setAttribute('graphviz.graph.ratio', 10 / 16);

/**
 * @var FixerInterface $higher
 * @var FixerInterface $lower
 */
foreach ($factory->provideFixersPriorityCases() as [$higher, $lower]) {
    $nameHigher = $higher->getName();
    $nameLower  = $lower->getName();

    if (!isset($vertices[$nameHigher])) {
        $vertices[$nameHigher] = $graph->createVertex($nameHigher);
        $vertices[$nameHigher]->setBalance($higher->getPriority());
    }

    if (!isset($vertices[$nameLower])) {
        $vertices[$nameLower] = $graph->createVertex($nameLower);
        $vertices[$nameLower]->setBalance($lower->getPriority());
    }

    $edge = $vertices[$nameHigher]->createEdgeTo($vertices[$nameLower]);
    $edge->setAttribute('graphviz.color', 'grey');
}

$graphViz = new GraphViz();
$graphViz->display($graph);
