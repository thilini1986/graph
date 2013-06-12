<?php

namespace Fhaculty\Graph\Algorithm\Tree;

use Fhaculty\Graph\Algorithm\Tree\Base as Tree;
use Fhaculty\Graph\Exception\UnderflowException;
use Fhaculty\Graph\Exception\UnexpectedValueException;
use Fhaculty\Graph\Vertex;

/**
 *
 * @link http://en.wikipedia.org/wiki/Spaghetti_stack
 * @see OutTree
 */
abstract class BaseDirected extends Tree
{
    /**
     * get root vertex for this in-tree
     *
     * @return Vertex
     * @throws UnderflowException if given graph is empty or no possible root candidate was found (check isTree()!)
     */
    public function getVertexRoot()
    {
        foreach ($this->graph->getVertices() as $vertex) {
            if ($this->isVertexPossibleRoot($vertex)) {
                return $vertex;
            }
        }
        throw new UnderflowException('No possible root found. Either empty graph or no Vertex with proper degree found.');
    }

    /**
     * checks if this is a tree
     *
     * @return boolean
     * @uses Graph::isEmpty()
     * @uses self::getVertexRoot() to actually check tree
     */
    public function isTree()
    {
        if ($this->graph->isEmpty()) {
            return true;
        }

        try {
            $root = $this->getVertexRoot();
        }
        catch (UnderflowException $e) {
            return false;
        }
        catch (UnexpectedValueException $e) {
            return false;
        }

        try {
            $num = count($this->getVerticesSubtree($root));
        }
        catch (UnexpectedValueException $e) {
            return false;
        }

        return ($num === $this->graph->getNumberOfVertices());
    }

    /**
     * get parent vertex for given $vertex
     *
     * @param Vertex $vertex
     * @throws UnderflowException if vertex has no parent (is a root vertex)
     * @throws UnexpectedValueException if vertex has more than one possible parent (check isTree()!)
     * @return Vertex
     */
    public function getVertexParent(Vertex $vertex)
    {
        $parents = $this->getVerticesParent($vertex);
        if (count($parents) !== 1) {
            if (!$parents) {
                throw new UnderflowException('No parents for given vertex found');
            } else {
                throw new UnexpectedValueException('More than one parent');
            }
        }
        return current($parents);
    }

    /**
     * get array of child vertices for given $vertex
     *
     * @param Vertex $vertex
     * @return Vertex[]
     * @throws UnexpectedValueException if the given $vertex contains invalid / parallel links (check isTree()!)
     */
    abstract public function getVerticesChildren(Vertex $vertex);

    abstract protected function getVerticesParent(Vertex $vertex);

    protected function isVertexPossibleRoot(Vertex $vertex)
    {
        return (count($this->getVerticesParent($vertex)) === 0);
    }

    /**
     * checks if the given $vertex is a leaf (outermost vertex with no children)
     *
     * @param Vertex $vertex
     * @return boolean
     * @uses self::getVerticesChildren()
     */
    public function isVertexLeaf(Vertex $vertex)
    {
        return (count($this->getVerticesChildren($vertex)) === 0);
    }

    public function isVertexInternal(Vertex $vertex)
    {
        return ($this->getVerticesParent($vertex) && $this->getVerticesChildren($vertex));
    }

    /**
     * get degree of tree (maximum number of children)
     *
     * @return int
     * @throws UnderflowException for empty graphs
     * @uses Graph::getVertices()
     * @uses self::getVerticesChildren()
     */
    public function getDegree()
    {
        $max = null;
        foreach ($this->graph->getVertices() as $vertex) {
            $num = count($this->getVerticesChildren($vertex));
            if ($max === null || $num > $max) {
                $max = $num;
            }
        }
        if ($max === null) {
            throw new UnderflowException('No vertices found');
        }
        return $max;
    }

    /**
     * get depth of given $vertex (number of edges between root vertex)
     *
     * root has depth zero
     *
     * @param Vertex $vertex
     * @return int
     * @throws UnderflowException for empty graphs
     * @throws UnexpectedValueException if there's no path to root node (check isTree()!)
     * @uses self::getVertexRoot()
     * @uses self::getVertexParent() for each step
     */
    public function getDepthVertex(Vertex $vertex)
    {
        $root = $this->getVertexRoot();

        $depth = 0;
        while ($vertex !== $root) {
            $vertex = $this->getVertexParent($vertex);
            ++$depth;
        }
        return $depth;
    }

    /**
     * get height of this tree (longest downward path to a leaf)
     *
     * a single vertex graph has height zero
     *
     * @return int
     * @throws UnderflowException for empty graph
     * @uses self::getVertexRoot()
     * @uses self::getHeightVertex()
     */
    public function getHeight()
    {
        return $this->getHeightVertex($this->getVertexRoot());
    }

    /**
     * get height of given vertex (longest downward path to a leaf)
     *
     * leafs has height zero
     *
     * @param Vertex $vertex
     * @return int
     * @uses self::getVerticesChildren() to get children of given vertex
     * @uses self::getHeightVertex() to recurse into sub-children
     */
    public function getHeightVertex(Vertex $vertex)
    {
        $max = 0;
        foreach ($this->getVerticesChildren($vertex) as $vertex) {
            $height = $this->getHeightVertex($vertex) + 1;
            if ($height > $max) {
                $max = $height;
            }
        }
        return $max;
    }

    /**
     * get all vertices that are in the subtree of the given $vertex (which IS included)
     *
     * root vertex will return the whole tree, leaf vertices will only return themselves
     *
     * @param Vertex $vertex
     * @throws UnexpectedValueException if there are invalid edges (check isTree()!)
     * @return Vertex[]
     * @uses self::getVerticesSubtreeRecursive()
     * @uses self::getVerticesSubtree()
     */
    public function getVerticesSubtree(Vertex $vertex)
    {
        $vertices = array();
        $this->getVerticesSubtreeRecursive($vertex, $vertices);

        return $vertices;
    }

    /**
     * helper method to get recursively get subtree for given $vertex
     *
     * @param Vertex   $vertex
     * @param Vertex[] $vertices
     * @throws UnexpectedValueException if multiple links were found to the given edge (check isTree()!)
     * @uses self::getVerticesChildren()
     * @uses self::getVerticesSubtreeRecursive() to recurse into subtrees
     */
    private function getVerticesSubtreeRecursive(Vertex $vertex, &$vertices)
    {
        $vid = $vertex->getId();
        if (isset($vertices[$vid])) {
            throw new UnexpectedValueException('Multiple links found');
        }
        $vertices[$vid] = $vertex;

        foreach ($this->getVerticesChildren($vertex) as $vertexChild) {
            $this->getVerticesSubtreeRecursive($vertexChild, $vertices);
        }
    }

    /**
     * get all vertices below the given $vertex (which is NOT included)
     *
     * think of this as the recursive version of getVerticesChildren()
     *
     * @param Vertex $vertex
     * @return Vertex[]
     * @throws UnexpectedValueException if there are invalid edges (check isTree()!)
     * @uses self::getVerticesSubtree()
     */
    public function getVerticesDescendant(Vertex $vertex)
    {
        $vertices = $this->getVerticesSubtree($vertex);
        unset($vertices[$vertex->getId()]);

        return $vertices;
    }
}
