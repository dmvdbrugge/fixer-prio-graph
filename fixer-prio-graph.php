<?php

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\GraphViz\GraphViz;
use PhpCsFixer\Fixer\Comment\CommentToPhpdocFixer;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocScalarFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesFixer;
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
//$graph->setAttribute('graphviz.graph.ratio', 9 / 22); // Ultra-wide
$graph->setAttribute('graphviz.graph.ratio', 10 / 16); // Normal wide screen

$addPriorityCase = function (FixerInterface $higher, FixerInterface $lower) use ($graph, &$vertices) {
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

    if (!$vertices[$nameHigher]->hasEdgeTo($vertices[$nameLower])) {
        $edge = $vertices[$nameHigher]->createEdgeTo($vertices[$nameLower]);
        $edge->setAttribute('graphviz.color', 'grey');
    }
};

/**
 * @var FixerInterface $higher
 * @var FixerInterface $lower
 */
foreach ($factory->provideFixersPriorityCases() as [$higher, $lower]) {
    $addPriorityCase($higher, $lower);
}

foreach ($factory->provideFixersPrioritySpecialPhpdocCases() as [$higher, $lower]) {
    if (
        ($higher instanceof AlignMultilineCommentFixer && !$lower instanceof CommentToPhpdocFixer)
        || ($higher instanceof CommentToPhpdocFixer && !$lower instanceof PhpdocToCommentFixer)
        || ($higher instanceof PhpdocToCommentFixer && !$lower instanceof PhpdocIndentFixer)
        || ($higher instanceof PhpdocIndentFixer && !$lower instanceof PhpdocTypesFixer)
        || ($higher instanceof PhpdocTypesFixer && !$lower instanceof PhpdocScalarFixer)
    ) {
        continue;
    }

    $addPriorityCase($higher, $lower);
}

$graphViz = new GraphViz();
$graphViz->display($graph);
