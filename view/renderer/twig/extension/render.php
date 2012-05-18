<?php

class view_renderer_twig_extension_render extends Twig_Extension
{
    public function _getGlobals()
    {
        return array(
            'render' => new Twig_Function_Method($this, 'render'),
        );
    }

    public function getTokenParsers()
    {
        return array(new RenderTokenParser());
    }

    public function renderAction($controller, array $attributes = array(), array $options = array())
    {
        $request = new Zend_Controller_Request_Http();
        $request->setRequestUri($controller);
        $request->setParams($attributes);
        $web = clone web::instance();
        return $web->run($request);
    }

    public function getName()
    {
        return 'actions';
    }
}

/**
 *
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RenderTokenParser extends Twig_TokenParser
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
        $expr = $this->parser->getExpressionParser()->parseExpression();

        // attributes
        if ($this->parser->getStream()->test(Twig_Token::NAME_TYPE, 'with')) {
            $this->parser->getStream()->next();

            $hasAttributes = true;
            $attributes = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $hasAttributes = false;
            $attributes = new Twig_Node_Expression_Array(array(), $token->getLine());
        }

        // options
        if ($hasAttributes && $this->parser->getStream()->test(Twig_Token::PUNCTUATION_TYPE, ',')) {
            $this->parser->getStream()->next();

            $options = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $options = new Twig_Node_Expression_Array(array(), $token->getLine());
        }

        $this->parser->getStream()->expect(Twig_Token::BLOCK_END_TYPE);

        return new RenderNode($expr, $attributes, $options, $token->getLine(), $this->getTag());
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'render';
    }
}


/**
 * Represents a render node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RenderNode extends Twig_Node
{
    public function __construct(Twig_Node_Expression $expr, Twig_Node_Expression $attributes, Twig_Node_Expression $options, $lineno, $tag = null)
    {
        parent::__construct(array('expr' => $expr, 'attributes' => $attributes, 'options' => $options), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler $compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("echo \$this->env->getExtension('actions')->renderAction(")
            ->subcompile($this->getNode('expr'))
            ->raw(', ')
            ->subcompile($this->getNode('attributes'))
            ->raw(', ')
            ->subcompile($this->getNode('options'))
            ->raw(");\n")
        ;
    }
}