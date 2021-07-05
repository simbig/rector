<?php

declare (strict_types=1);
namespace Rector\Php71\Rector\Assign;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Cast\Array_ as ArrayCast;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\PropertyProperty;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use Rector\Core\Rector\AbstractRector;
use Rector\Php71\NodeFinder\EmptyStringDefaultPropertyFinder;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://stackoverflow.com/a/41000866/1348344 https://3v4l.org/ABDNv
 *
 * @see \Rector\Tests\Php71\Rector\Assign\AssignArrayToStringRector\AssignArrayToStringRectorTest
 */
final class AssignArrayToStringRector extends \Rector\Core\Rector\AbstractRector
{
    /**
     * @var PropertyProperty[]
     */
    private $emptyStringProperties = [];
    /**
     * @var \Rector\Php71\NodeFinder\EmptyStringDefaultPropertyFinder
     */
    private $emptyStringDefaultPropertyFinder;
    public function __construct(\Rector\Php71\NodeFinder\EmptyStringDefaultPropertyFinder $emptyStringDefaultPropertyFinder)
    {
        $this->emptyStringDefaultPropertyFinder = $emptyStringDefaultPropertyFinder;
    }
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('String cannot be turned into array by assignment anymore', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
$string = '';
$string[] = 1;
CODE_SAMPLE
, <<<'CODE_SAMPLE'
$string = [];
$string[] = 1;
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Expr\Assign::class];
    }
    /**
     * @param \PhpParser\Node $node
     */
    public function refactor($node) : ?\PhpParser\Node
    {
        $this->emptyStringProperties = $this->emptyStringDefaultPropertyFinder->find($node);
        // only array with no explicit key assign, e.g. "$value[] = 5";
        if (!$node->var instanceof \PhpParser\Node\Expr\ArrayDimFetch) {
            return null;
        }
        if ($node->var->dim !== null) {
            return null;
        }
        $arrayDimFetchNode = $node->var;
        /** @var Variable|PropertyFetch|StaticPropertyFetch|Expr $variable */
        $variable = $arrayDimFetchNode->var;
        // set default value to property
        if (($variable instanceof \PhpParser\Node\Expr\PropertyFetch || $variable instanceof \PhpParser\Node\Expr\StaticPropertyFetch) && $this->refactorPropertyFetch($variable)) {
            return $node;
        }
        // fallback to variable, property or static property = '' set
        if ($this->processVariable($node, $variable)) {
            return $node;
        }
        $isFoundPrev = (bool) $this->betterNodeFinder->findFirstPreviousOfNode($variable, function (\PhpParser\Node $node) use($variable) : bool {
            return $this->nodeComparator->areNodesEqual($node, $variable);
        });
        if (!$isFoundPrev) {
            return null;
        }
        // there is "$string[] = ...;", which would cause error in PHP 7+
        // fallback - if no array init found, retype to (array)
        $assign = new \PhpParser\Node\Expr\Assign($variable, new \PhpParser\Node\Expr\Cast\Array_($variable));
        $this->addNodeAfterNode(clone $node, $node);
        return $assign;
    }
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetchExpr
     */
    private function refactorPropertyFetch($propertyFetchExpr) : bool
    {
        foreach ($this->emptyStringProperties as $emptyStringProperty) {
            if (!$this->nodeNameResolver->areNamesEqual($emptyStringProperty, $propertyFetchExpr)) {
                continue;
            }
            $emptyStringProperty->default = new \PhpParser\Node\Expr\Array_();
            return \true;
        }
        return \false;
    }
    /**
     * @param \PhpParser\Node\Expr\Variable|\PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch|\PhpParser\Node\Expr $expr
     */
    private function processVariable(\PhpParser\Node\Expr\Assign $assign, $expr) : bool
    {
        if ($this->shouldSkipVariable($expr)) {
            return \true;
        }
        $variableAssign = $this->betterNodeFinder->findFirstPrevious($assign, function (\PhpParser\Node $node) use($expr) : bool {
            if (!$node instanceof \PhpParser\Node\Expr\Assign) {
                return \false;
            }
            if (!$this->nodeComparator->areNodesEqual($node->var, $expr)) {
                return \false;
            }
            // we look for variable assign = string
            if (!$node->expr instanceof \PhpParser\Node\Scalar\String_) {
                return \false;
            }
            return $this->valueResolver->isValue($node->expr, '');
        });
        if ($variableAssign instanceof \PhpParser\Node\Expr\Assign) {
            $variableAssign->expr = new \PhpParser\Node\Expr\Array_();
            return \true;
        }
        return \false;
    }
    /**
     * @param \PhpParser\Node\Expr $expr
     */
    private function shouldSkipVariable($expr) : bool
    {
        $staticType = $this->getStaticType($expr);
        if ($staticType instanceof \PHPStan\Type\ErrorType) {
            return \false;
        }
        if ($staticType instanceof \PHPStan\Type\UnionType) {
            return !($staticType->isSuperTypeOf(new \PHPStan\Type\ArrayType(new \PHPStan\Type\MixedType(), new \PHPStan\Type\MixedType()))->yes() && $staticType->isSuperTypeOf(new \PHPStan\Type\Constant\ConstantStringType(''))->yes());
        }
        return !$staticType instanceof \PHPStan\Type\StringType;
    }
}
