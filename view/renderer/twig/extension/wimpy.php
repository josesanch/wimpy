<?php

class view_renderer_twig_extension_wimpy extends Twig_Extension
{

    public function getFilters()
    {
        return array(
            'trans'   => new Twig_Filter_Function('__'),
            't'   => new Twig_Filter_Function('__'),
            'bytes'   => new Twig_Filter_Function('html_template_filters::bytes'),
            'nl2br'   => new Twig_Filter_Function('html_template_filters::nl2br'),
            'money'   => new Twig_Filter_Function('html_template_filters::money'),
            'seconds' => new Twig_Filter_Function('html_template_filters::seconds'),
            'highlight'  => new Twig_Filter_Method($this, 'highlight'),
        );

    }

    public function highlight($sentence, $expr, $allWords = false)
    {
        if ($allWords)
            $expr = preg_replace("/\s/", '|', $expr);
        return preg_replace('/(' . $expr . ')/i', '<span class="hightlighted">\1</span>', $sentence);
    }

    public function getTokenParsers()
    {
        return array(new Twig_Extensions_TokenParser_Trans());
    }

    public function getName()
    {
        return 'wimpy';
    }

}

class Twig_TokenParser_js extends Twig_TokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(Twig_Token $token)
    {
        $this->parser->setParent($this->parser->getExpressionParser()->parseExpression());
        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);
        return null;
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'js';
    }
}


/**
 * Represents a trans node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Extensions_Node_Trans extends Twig_Node
{
    public function __construct(Twig_NodeInterface $body, Twig_NodeInterface $plural = null, Twig_Node_Expression $count = null, $lineno, $tag = null)
    {
        parent::__construct(array('count' => $count, 'body' => $body, 'plural' => $plural), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        list($msg, $vars) = $this->compileString($this->getNode('body'));

        if (null !== $this->getNode('plural')) {
            list($msg1, $vars1) = $this->compileString($this->getNode('plural'));

            $vars = array_merge($vars, $vars1);
        }

        $function = null === $this->getNode('plural') ? '__' : 'ngettext';

        if ($vars) {
            $compiler
                ->write('echo strtr('.$function.'(')
                ->subcompile($msg)
            ;

            if (null !== $this->getNode('plural')) {
                $compiler
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->getNode('count'))
                    ->raw(')')
                ;
            }

            $compiler->raw('), array(');

            foreach ($vars as $var) {
                if ('count' === $var->getAttribute('name')) {
                    $compiler
                        ->string('%count%')
                        ->raw(' => abs(')
                        ->subcompile($this->getNode('count'))
                        ->raw('), ')
                    ;
                } else {
                    $compiler
                        ->string('%'.$var->getAttribute('name').'%')
                        ->raw(' => ')
                        ->subcompile($var)
                        ->raw(', ')
                    ;
                }
            }

            $compiler->raw("));\n");
        } else {
            $compiler
                ->write('echo '.$function.'(')
                ->subcompile($msg)
            ;

            if (null !== $this->getNode('plural')) {
                $compiler
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->getNode('count'))
                    ->raw(')')
                ;
            }

            $compiler->raw(');');
        }
    }

    protected function compileString(Twig_NodeInterface $body)
    {
        if ($body instanceof Twig_Node_Expression_Name || $body instanceof Twig_Node_Expression_Constant) {
            return array($body, array());
        }

        $vars = array();

        if (count($body)) {
            $msg = '';

            foreach ($body as $node) {
                if ($node instanceof Twig_Node_Print) {
                    $n = $node->getNode('expr');
                    while ($n instanceof Twig_Node_Expression_Filter) {
                        $n = $n->getNode('node');
                    }
                    $msg .= sprintf('%%%s%%', $n->getAttribute('name'));
                    $vars[] = new Twig_Node_Expression_Name($n->getAttribute('name'), $n->getLine());
                } else {
                    $msg .= $node->getAttribute('data');
                }
            }
        } else {
            $msg = $body->getAttribute('data');
        }

        return array(new Twig_Node(array(new Twig_Node_Expression_Constant(trim($msg), $body->getLine()))), $vars);
    }
}





/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Twig_Extensions_TokenParser_Trans extends Twig_TokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $count = null;
        $plural = null;

        if (!$stream->test(Twig_Token::BLOCK_END_TYPE)) {
            $body = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
            $body = $this->parser->subparse(array($this, 'decideForFork'));
            if ('plural' === $stream->next()->getValue()) {
                $count = $this->parser->getExpressionParser()->parseExpression();
                $stream->expect(Twig_Token::BLOCK_END_TYPE);
                $plural = $this->parser->subparse(array($this, 'decideForEnd'), true);
            }
        }

        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        $this->checkTransString($body, $lineno);

        return new Twig_Extensions_Node_Trans($body, $plural, $count, $lineno, $this->getTag());
    }

    public function decideForFork($token)
    {
        return $token->test(array('plural', 'endtrans'));
    }

    public function decideForEnd($token)
    {
        return $token->test('endtrans');
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     */
    public function getTag()
    {
        return 'trans';
    }

    protected function checkTransString(Twig_NodeInterface $body, $lineno)
    {
        foreach ($body as $i => $node) {
            if (
                $node instanceof Twig_Node_Text
                ||
                ($node instanceof Twig_Node_Print && $node->getNode('expr') instanceof Twig_Node_Expression_Name)
            ) {
                continue;
            }

            throw new Twig_Error_Syntax(sprintf('The text to be translated with "trans" can only contain references to simple variables'), $lineno);
        }
    }
}
