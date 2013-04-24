<?php
/**
 * File containing the EzcDatabase location gateway class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\Persistence\SqlNg\Content\Location\Gateway;

use eZ\Publish\Core\Persistence\SqlNg\Content\Location\Gateway;
use eZ\Publish\Core\Persistence\Legacy\EzcDbHandler;
use eZ\Publish\SPI\Persistence\Content\ContentInfo;
use eZ\Publish\SPI\Persistence\Content\Location;
use eZ\Publish\SPI\Persistence\Content\Location\UpdateStruct;
use eZ\Publish\SPI\Persistence\Content\Location\CreateStruct;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\Core\Base\Exceptions\NotFoundException as NotFound;
use RuntimeException;

/**
 * Location gateway implementation using the zeta database component.
 */
class EzcDatabase extends Gateway
{
    /**
     * Database dbHandler
     *
     * @var \EzcDbHandler
     */
    protected $dbHandler;

    /**
     * Construct from database dbHandler
     *
     * @param \eZ\Publish\Core\Persistence\Legacy\EzcDbHandler $dbHandler
     *
     * @return void
     */
    public function __construct( EzcDbHandler $dbHandler )
    {
        $this->dbHandler = $dbHandler;
    }

    /**
     * Returns an array with basic node data
     *
     * We might want to cache this, since this method is used by about every
     * method in the location dbHandler.
     *
     * @param mixed $nodeId
     * @param int $status
     *
     * @return array
     */
    public function getBasicNodeData( $nodeId, $status = self::PUBLISHED )
    {
        $query = $this->dbHandler->createSelectQuery();
        $query
            ->select( '*' )
            ->from( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->where(
                $query->expr->lAnd(
                    $query->expr->eq(
                        $this->dbHandler->quoteColumn( 'location_id' ),
                        $query->bindValue( $nodeId )
                    ),
                    $query->expr->eq(
                        $this->dbHandler->quoteColumn( 'status' ),
                        $query->bindValue( $status )
                    )
                )
            );
        $statement = $query->prepare();
        $statement->execute();

        if ( $row = $statement->fetch( \PDO::FETCH_ASSOC ) )
        {
            return $row;
        }

        throw new NotFound( 'location', $nodeId );
    }

    /**
     * Returns an array with basic node data
     *
     * @param mixed $remoteId
     *
     * @return array
     */
    public function getBasicNodeDataByRemoteId( $remoteId )
    {
        $query = $this->dbHandler->createSelectQuery();
        $query
            ->select( '*' )
            ->from( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->where(
                $query->expr->lAnd(
                    $query->expr->eq(
                        $this->dbHandler->quoteColumn( 'remote_id' ),
                        $query->bindValue( $remoteId )
                    ),
                    $query->expr->eq(
                        $this->dbHandler->quoteColumn( 'status' ),
                        $query->bindValue( self::PUBLISHED )
                    )
                )
            );
        $statement = $query->prepare();
        $statement->execute();

        if ( $row = $statement->fetch( \PDO::FETCH_ASSOC ) )
        {
            return $row;
        }

        throw new NotFound( 'location', $remoteId );
    }

    /**
     * Loads data for all Locations for $contentId, optionally only in the
     * subtree starting at $rootLocationId
     *
     * @param int $contentId
     * @param int $rootLocationId
     *
     * @return array
     */
    public function loadLocationDataByContent( $contentId, $rootLocationId = null )
    {
        $query = $this->dbHandler->createSelectQuery();
        $query
            ->select( '*' )
            ->from( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->where(
                $query->expr->lAnd(
                    $query->expr->eq(
                        $this->dbHandler->quoteColumn( 'content_id' ),
                        $query->bindValue( $contentId )
                    ),
                    $query->expr->eq(
                        $this->dbHandler->quoteColumn( 'status' ),
                        $query->bindValue( self::PUBLISHED )
                    )
                )
            );

        if ( $rootLocationId !== null )
        {
            $this->applySubtreeLimitation( $query, $rootLocationId );
        }

        $statement = $query->prepare();
        $statement->execute();

        return $statement->fetchAll( \PDO::FETCH_ASSOC );
    }

    /**
     * Find all content in the given subtree
     *
     * @param mixed $sourceId
     *
     * @return array
     */
    public function getSubtreeContent( $sourceId )
    {
        throw new \RuntimeException( "@TODO: Implement" );
    }

    /**
     * Returns data for the first level children of the location identified by given $locationId
     *
     * @param mixed $locationId
     *
     * @return array
     */
    public function getChildren( $locationId )
    {
        throw new \RuntimeException( "@TODO: Implement" );
    }

    /**
     * Update path strings to move nodes in the ezcontent_location table
     *
     * This query can likely be optimized to use some more advanced string
     * operations, which then depend on the respective database.
     *
     * @todo optimize
     * @param string $sourceNodeData
     * @param string $destinationNodeData
     *
     * @return void
     */
    public function moveSubtreeNodes( $sourceNodeData, $destinationNodeData )
    {
        $fromPathString = $sourceNodeData["path_string"];

        /** @var $query \ezcQuerySelect */
        $query = $this->dbHandler->createSelectQuery();
        $query
            ->select(
                $this->dbHandler->quoteColumn( 'location_id' ),
                $this->dbHandler->quoteColumn( 'parent_id' ),
                $this->dbHandler->quoteColumn( 'path_string' )
            )
            ->from( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->where(
                $query->expr->like(
                    $this->dbHandler->quoteColumn( 'path_string' ),
                    $query->bindValue( $fromPathString . '%' )
                )
            );
        $statement = $query->prepare();
        $statement->execute();

        $rows = $statement->fetchAll();
        $oldParentPathString = implode( '/', array_slice( explode( '/', $fromPathString ), 0, -2 ) ) . '/';
        foreach ( $rows as $row )
        {
            // Prefixing ensures correct replacement when old parent is root node
            $newPathString = str_replace(
                "prefix" . $oldParentPathString,
                $destinationNodeData["path_string"],
                "prefix" . $row['path_string']
            );

            $newParentId = $row['parent_id'];
            if ( $row['path_string'] === $fromPathString )
            {
                $newParentId = (int)implode( '', array_slice( explode( '/', $newPathString ), -3, 1 ) );
            }

            /** @var $query \ezcQueryUpdate */
            $query = $this->dbHandler->createUpdateQuery();
            $query
                ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
                ->set(
                    $this->dbHandler->quoteColumn( 'path_string' ),
                    $query->bindValue( $newPathString )
                )
                ->set(
                    $this->dbHandler->quoteColumn( 'depth' ),
                    $query->bindValue( substr_count( $newPathString, '/' ) - 2 )
                )
                ->set(
                    $this->dbHandler->quoteColumn( 'parent_id' ),
                    $query->bindValue( $newParentId )
                )
                ->where(
                    $query->expr->eq(
                        $this->dbHandler->quoteColumn( 'location_id' ),
                        $query->bindValue( $row['location_id'] )
                    )
                );
            $query->prepare()->execute();
        }
    }

    /**
     * Sets a location to be hidden, and it self + all children to invisible.
     *
     * @param string $pathString
     */
    public function hideSubtree( $pathString )
    {
        $query = $this->dbHandler->createUpdateQuery();
        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'is_hidden' ),
                $query->bindValue( 1 )
            )->where(
                $query->expr->eq(
                    $this->dbHandler->quoteColumn( 'path_string' ),
                    $query->bindValue( $pathString )
                )
            );
        $query->prepare()->execute();

        $query = $this->dbHandler->createUpdateQuery();
        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'is_invisible' ),
                $query->bindValue( 1 )
            )->where(
                $query->expr->like(
                    $this->dbHandler->quoteColumn( 'path_string' ),
                    $query->bindValue( $pathString . '%' )
                )
            );
        $query->prepare()->execute();
    }

    /**
     * Sets a location to be unhidden, and self + children to visible unless a parent is hiding the tree.
     * If not make sure only children down to first hidden node is marked visible.
     *
     * @param string $pathString
     */
    public function unHideSubtree( $pathString )
    {
        // Unhide the requested node
        $query = $this->dbHandler->createUpdateQuery();
        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'is_hidden' ),
                $query->bindValue( 0 )
            )
            ->where(
                $query->expr->eq(
                    $this->dbHandler->quoteColumn( 'path_string' ),
                    $query->bindValue( $pathString )
                )
            );
        $query->prepare()->execute();

        // Check if any parent nodes are explicitly hidden
        $query = $this->dbHandler->createSelectQuery();
        $query
            ->select( $this->dbHandler->quoteColumn( 'path_string' ) )
            ->from( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->where(
                $query->expr->lAnd(
                    $query->expr->eq(
                        $this->dbHandler->quoteColumn( 'is_hidden' ),
                        $query->bindValue( 1 )
                    ),
                    $query->expr->in(
                        $this->dbHandler->quoteColumn( 'location_id' ),
                        array_filter( explode( '/', $pathString ) )
                    )
                )
            );
        $statement = $query->prepare();
        $statement->execute();
        if ( count( $statement->fetchAll( \PDO::FETCH_COLUMN ) ) )
        {
            // There are parent nodes set hidden, so that we can skip marking
            // something visible again.
            return;
        }

        // Find nodes of explicitly hidden subtrees in the subtree which
        // should be unhidden
        $query = $this->dbHandler->createSelectQuery();
        $query
            ->select( $this->dbHandler->quoteColumn( 'path_string' ) )
            ->from( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->where(
                $query->expr->lAnd(
                    $query->expr->eq(
                        $this->dbHandler->quoteColumn( 'is_hidden' ),
                        $query->bindValue( 1 )
                    ),
                    $query->expr->like(
                        $this->dbHandler->quoteColumn( 'path_string' ),
                        $query->bindValue( $pathString . '%' )
                    )
                )
            );
        $statement = $query->prepare();
        $statement->execute();
        $hiddenSubtrees = $statement->fetchAll( \PDO::FETCH_COLUMN );

        $query = $this->dbHandler->createUpdateQuery();
        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'is_invisible' ),
                $query->bindValue( 0 )
            );

        // Build where expression selecting the nodes, which should be made
        // visible again
        $where = $query->expr->like(
            $this->dbHandler->quoteColumn( 'path_string' ),
            $query->bindValue( $pathString . '%' )
        );
        if ( count( $hiddenSubtrees ) )
        {
            $dbHandler = $this->dbHandler;
            $where = $query->expr->lAnd(
                $where,
                $query->expr->lAnd(
                    array_map(
                        function ( $pathString ) use ( $query, $dbHandler )
                        {
                            return $query->expr->not(
                                $query->expr->like(
                                    $dbHandler->quoteColumn( 'path_string' ),
                                    $query->bindValue( $pathString . '%' )
                                )
                            );
                        },
                        $hiddenSubtrees
                    )
                )
            );
        }
        $query->where( $where );
        $statement = $query->prepare()->execute();
    }

    /**
     * Swaps the content object being pointed to by a location object.
     *
     * Make the location identified by $locationId1 refer to the Content
     * referred to by $locationId2 and vice versa.
     *
     * @param mixed $locationId1
     * @param mixed $locationId2
     *
     * @return boolean
     */
    public function swap( $locationId1, $locationId2 )
    {
        $query = $this->dbHandler->createSelectQuery();
        $query
            ->select(
                $this->dbHandler->quoteColumn( 'location_id' ),
                $this->dbHandler->quoteColumn( 'content_id' ),
                $this->dbHandler->quoteColumn( 'content_version_no' )
            )
            ->from( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->where(
                $query->expr->in(
                    $this->dbHandler->quoteColumn( 'location_id' ),
                    array( $locationId1, $locationId2 )
                )
            );
        $statement = $query->prepare();
        $statement->execute();
        $contentObjects = array();
        foreach ( $statement->fetchAll() as $row )
        {
            $contentObjects[$row['location_id']] = $row;
        }

        if ( !isset($contentObjects[$locationId1] ) )
        {
            throw new NotFound( 'location', $locationId1 );
        }

        if ( !isset($contentObjects[$locationId2] ) )
        {
            throw new NotFound( 'location', $locationId2 );
        }

        $query = $this->dbHandler->createUpdateQuery();
        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'content_id' ),
                $query->bindValue( $contentObjects[$locationId2]['content_id'] )
            )
            ->set(
                $this->dbHandler->quoteColumn( 'content_version_no' ),
                $query->bindValue( $contentObjects[$locationId2]['content_version_no'] )
            )
            ->where(
                $query->expr->eq(
                    $this->dbHandler->quoteColumn( 'location_id' ),
                    $query->bindValue( $locationId1 )
                )
            );
        $query->prepare()->execute();

        $query = $this->dbHandler->createUpdateQuery();
        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'content_id' ),
                $query->bindValue( $contentObjects[$locationId1]['content_id'] )
            )
            ->set(
                $this->dbHandler->quoteColumn( 'content_version_no' ),
                $query->bindValue( $contentObjects[$locationId1]['content_version_no'] )
            )
            ->where(
                $query->expr->eq(
                    $this->dbHandler->quoteColumn( 'location_id' ),
                    $query->bindValue( $locationId2 )
                )
            );
        $query->prepare()->execute();
    }

    /**
     * Creates a new location in given $parentNode
     *
     * @param \eZ\Publish\SPI\Persistence\Content\Location\CreateStruct $createStruct
     * @param array $parentNode
     *
     * @return \eZ\Publish\SPI\Persistence\Content\Location
     */
    public function create( CreateStruct $createStruct, $parentNodeData, $status )
    {
        $location = new Location();

        $this->dbHandler->beginTransaction();
        $query = $this->dbHandler->createInsertQuery();
        $query
            ->insertInto(
                $this->dbHandler->quoteTable( 'ezcontent_location' )
            )->set(
                $this->dbHandler->quoteColumn( 'location_id' ),
                $this->dbHandler->getAutoIncrementValue( 'ezcontent_location', 'location_id' )
            )->set(
                $this->dbHandler->quoteColumn( 'status' ),
                $query->bindValue( $status, null, \PDO::PARAM_INT )
            )->set(
                $this->dbHandler->quoteColumn( 'content_id' ),
                $query->bindValue( $location->contentId = $createStruct->contentId, null, \PDO::PARAM_INT )
            )->set(
                $this->dbHandler->quoteColumn( 'content_version_no' ),
                $query->bindValue( $createStruct->contentVersion, null, \PDO::PARAM_INT )
            )->set(
                $this->dbHandler->quoteColumn( 'depth' ),
                $query->bindValue( $location->depth = $parentNodeData ? $parentNodeData['depth'] + 1 : 1, null, \PDO::PARAM_INT )
            )->set(
                $this->dbHandler->quoteColumn( 'priority' ),
                $query->bindValue( $location->priority = $createStruct->priority, null, \PDO::PARAM_INT )
            )->set(
                $this->dbHandler->quoteColumn( 'remote_id' ),
                $query->bindValue( $location->remoteId = $createStruct->remoteId, null, \PDO::PARAM_STR )
            )->set(
                $this->dbHandler->quoteColumn( 'is_hidden' ),
                $query->bindValue( $location->hidden = $createStruct->hidden, null, \PDO::PARAM_INT )
            )->set(
                $this->dbHandler->quoteColumn( 'is_invisible' ),
                $query->bindValue( $location->invisible = $createStruct->invisible, null, \PDO::PARAM_INT )
            )->set(
                $this->dbHandler->quoteColumn( 'sort_field' ),
                $query->bindValue( $location->sortField = $createStruct->sortField, null, \PDO::PARAM_INT )
            )->set(
                $this->dbHandler->quoteColumn( 'sort_order' ),
                $query->bindValue( $location->sortOrder = $createStruct->sortOrder, null, \PDO::PARAM_INT )
            );
        $query->prepare()->execute();

        $location->id = $this->dbHandler->lastInsertId(
            $this->dbHandler->getSequenceName( 'ezcontent_location', 'location_id' )
        );

        $location->pathString = ( $parentNodeData ? $parentNodeData['path_string'] : '/' ) . $location->id . '/';
        $location->parentId = $parentNodeData ? $parentNodeData['location_id'] : $location->id;

        $query = $this->dbHandler->createUpdateQuery();
        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'path_string' ),
                $query->bindValue( $location->pathString )
            )->set(
                $this->dbHandler->quoteColumn( 'parent_id' ),
                $query->bindValue( $location->parentId, null, \PDO::PARAM_INT )
            )->where(
                $query->expr->eq(
                    $this->dbHandler->quoteColumn( 'location_id' ),
                    $query->bindValue( $location->id, null, \PDO::PARAM_INT )
                )
            );

        // Only set main_id, if it references another node. Otherwise deletion
        // of row is impossible
        if ( $createStruct->mainLocationId !== true )
        {
            $query->set(
                $this->dbHandler->quoteColumn( 'main_id' ),
                $query->bindValue( $createStruct->mainLocationId, null, \PDO::PARAM_INT )
            );
        }

        $query->prepare()->execute();
        $this->dbHandler->commit();

        return $location;
    }

    /**
     * Publish locations for content and update the version
     *
     * @param mixed $contentId
     * @param mixed $versionNo
     *
     * @return void
     */
    public function publishLocations( $contentId, $versionNo )
    {
        $query = $this->dbHandler->createUpdateQuery();
        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'status' ),
                $query->bindValue( self::PUBLISHED, null, \PDO::PARAM_INT )
            )->set(
                $this->dbHandler->quoteColumn( 'content_version_no' ),
                $query->bindValue( $versionNo, null, \PDO::PARAM_INT )
            )->where(
                $query->expr->eq(
                    $this->dbHandler->quoteColumn( 'content_id' ),
                    $query->bindValue( $contentId, null, \PDO::PARAM_INT )
                )
            );
        $query->prepare()->execute();
    }

    /**
     * Searches for the main nodeId of $contentId in $versionId
     *
     * @param int $contentId
     *
     * @return int|bool
     */
    private function getMainNodeId( $contentId )
    {
        throw new \RuntimeException( "@TODO: Implement" );
    }

    /**
     * Updates an existing location.
     *
     * @param \eZ\Publish\SPI\Persistence\Content\Location\UpdateStruct $location
     * @param int $locationId
     *
     * @return boolean
     */
    public function update( UpdateStruct $location, $locationId )
    {
        $query = $this->dbHandler->createUpdateQuery();

        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'priority' ),
                $query->bindValue( $location->priority )
            )
            ->set(
                $this->dbHandler->quoteColumn( 'remote_id' ),
                $query->bindValue( $location->remoteId )
            )
            ->set(
                $this->dbHandler->quoteColumn( 'sort_order' ),
                $query->bindValue( $location->sortOrder )
            )
            ->set(
                $this->dbHandler->quoteColumn( 'sort_field' ),
                $query->bindValue( $location->sortField )
            )
            ->where(
                $query->expr->eq(
                    $this->dbHandler->quoteColumn( 'location_id' ),
                    $locationId
                )
            );
        $statement = $query->prepare();
        $statement->execute();

        if ( $statement->rowCount() < 1 )
        {
            throw new NotFound( 'location', $locationId );
        }
    }

    /**
     * Deletes ezcontent_location row for given $locationId (id)
     *
     * @param mixed $locationId
     */
    public function removeLocation( $locationId )
    {
        throw new \RuntimeException( "@TODO: Implement" );
    }

    /**
     * Sends a subtree identified by given $pathString to the trash.
     *
     * The associated content object is left untouched.
     *
     * @param string $pathString
     *
     * @return boolean
     */
    public function trashSubtree( $pathString )
    {
        $query = $this->dbHandler->createUpdateQuery();
        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'status' ),
                $query->bindValue( self::DELETED )
            )
            ->where(
                $query->expr->like(
                    $this->dbHandler->quoteColumn( 'path_string' ),
                    $query->bindValue( $pathString . '%' )
                )
            );
        $statement = $query->prepare();
        $statement->execute();

        return ( $statement->rowCount() > 0 );
    }

    /**
     * Returns a trashed location to normal state.
     *
     * Recreates the originally trashed location in the new position. If no new
     * position has been specified, it will be tried to re-create the location
     * at the old position. If this is not possible ( because the old location
     * does not exist any more) and exception is thrown.
     *
     * @param mixed $locationId
     * @param mixed|null $newParentId
     *
     * @return \eZ\Publish\SPI\Persistence\Content\Location
     */
    public function untrashLocation( $locationId, $newParentId = null )
    {
        $locationData = $this->getBasicNodeData( $locationId, self::DELETED );

        $parentData = $locationData;
        if ( $locationData['parent_id'] != $locationId )
        {
            try {
                // Check if published parent exists
                $parentData = $this->getBasicNodeData( $locationData['parent_id'] );
            } catch ( NotFound $e ) {
                // Try new parent, throws exception, if it not exists, or is
                // not published.
                $parentData = $this->getBasicNodeData( $newParentId );
            }
        }

        $query = $this->dbHandler->createUpdateQuery();
        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'status' ),
                $query->bindValue( self::PUBLISHED )
            )
            ->where(
                $query->expr->like(
                    $this->dbHandler->quoteColumn( 'location_id' ),
                    $query->bindValue( $locationId )
                )
            );
        $statement = $query->prepare();
        $statement->execute();

        if ( $parentData['location_id'] != $locationData['parent_id'] ) {
            $this->moveSubtreeNodes( $locationData, $parentData );
        }

        return $locationData['location_id'];
    }

    /**
     * List trashed items
     *
     * @param int $offset
     * @param int $limit
     * @param array $sort
     *
     * @return array
     */
    public function listTrashed( $offset, $limit, array $sort = null )
    {
        $query = $this->dbHandler->createSelectQuery();
        $query
            ->select( '*' )
            ->from( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->where(
                $query->expr->eq(
                    $this->dbHandler->quoteColumn( 'status' ),
                    $query->bindValue( self::DELETED )
                )
            );

        // @REFACTOR: This hould use an somehow extensible handler
        // mechanism. switch case statements are impossible to
        // extend in any sane way.
        $sort = $sort ?: array();
        foreach ( $sort as $condition )
        {
            $sortDirection = $condition->direction === Query::SORT_ASC ? \ezcQuerySelect::ASC : \ezcQuerySelect::DESC;
            switch ( true )
            {
                case $condition instanceof SortClause\LocationDepth:
                    $query->orderBy( 'depth', $sortDirection );
                    break;

                case $condition instanceof SortClause\LocationPathString:
                    $query->orderBy( 'path_string', $sortDirection );
                    break;

                case $condition instanceof SortClause\LocationPriority:
                    $query->orderBy( 'priority', $sortDirection );
                    break;

                default:
                    // Only handle location related sort clauses. The others
                    // require data aggregation which is not sensible here.
                    // Since also criteria are yet ignored, because they are
                    // simply not used yet in eZ Publish, we skip that for now.
                    throw new RuntimeException( 'Unhandled sort clause: ' . get_class( $condition ) );
            }
        }

        if ( $limit !== null )
        {
            $query->limit( $limit, $offset );
        }

        $statement = $query->prepare();
        $statement->execute();

        return $statement->fetchAll( \PDO::FETCH_ASSOC );
    }

    /**
     * Removes every entries in the trash.
     * Will NOT remove associated content objects nor attributes.
     *
     * Basically truncates ezcontentobject_trash table.
     *
     * @return void
     */
    public function cleanupTrash()
    {
        throw new \RuntimeException( "@TODO: Implement" );
    }

    /**
     * Removes trashed element identified by $id from trash.
     * Will NOT remove associated content object nor attributes.
     *
     * @param int $id The trashed location Id
     *
     * @return void
     */
    public function removeElementFromTrash( $id )
    {
        throw new \RuntimeException( "@TODO: Implement" );
    }

    /**
     * Set section on all content objects in the subtree
     *
     * @param mixed $pathString
     * @param mixed $sectionId
     *
     * @return boolean
     */
    public function setSectionForSubtree( $pathString, $sectionId )
    {
        $query = $this->dbHandler->createUpdateQuery();

        $subSelect = $query->subSelect();
        $subSelect
            ->select( $this->dbHandler->quoteColumn( 'content_id' ) )
            ->from( $this->dbHandler->quoteTable( 'ezcontent_location' ) )
            ->where(
                $subSelect->expr->like(
                    $this->dbHandler->quoteColumn( 'path_string' ),
                    $subSelect->bindValue( $pathString . '%' )
                )
            );

        $query
            ->update( $this->dbHandler->quoteTable( 'ezcontent' ) )
            ->set(
                $this->dbHandler->quoteColumn( 'section_id' ),
                $query->bindValue( $sectionId )
            )
            ->where(
                $query->expr->in(
                    $this->dbHandler->quoteColumn( 'content_id' ),
                    $subSelect
                )
            );
        $query->prepare()->execute();
    }

    /**
     * Changes main location of content identified by given $contentId to location identified by given $locationId
     *
     * Updates ezcontent_location table for the given $contentId and eznode_assignment table for the given
     * $contentId, $parentLocationId and $versionNo
     *
     * @param mixed $contentId
     * @param mixed $locationId
     *
     * @return void
     */
    public function changeMainLocation( $contentId, $locationId )
    {
        $query = $this->dbHandler->createUpdateQuery();
        $query->update(
            $this->dbHandler->quoteTable( "ezcontent_location" )
        )->set(
            $this->dbHandler->quoteColumn( "main_id" ),
            $query->bindValue( $locationId, null, \PDO::PARAM_INT )
        )->where(
            $query->expr->eq(
                $this->dbHandler->quoteColumn( "content_id" ),
                $query->bindValue( $contentId, null, \PDO::PARAM_INT )
            )
        );
        $query->prepare()->execute();

        $query = $this->dbHandler->createUpdateQuery();
        $query->update(
            $this->dbHandler->quoteTable( "ezcontent_location" )
        )->set(
            $this->dbHandler->quoteColumn( "main_id" ),
            $query->bindValue( null )
        )->where(
            $query->expr->eq(
                $this->dbHandler->quoteColumn( "location_id" ),
                $query->bindValue( $locationId, null, \PDO::PARAM_INT )
            )
        );
        $query->prepare()->execute();
    }

    /**
     * Limits the given $query to the subtree starting at $rootLocationId
     *
     * @param \ezcQuery $query
     * @param string $rootLocationId
     *
     * @return void
     */
    protected function applySubtreeLimitation( \ezcQuery $query, $rootLocationId )
    {
        $query->where(
            $query->expr->like(
                $this->dbHandler->quoteColumn( 'path_string', 'ezcontent_location' ),
                $query->bindValue( '%/' . $rootLocationId . '/%' )
            )
        );
    }
}
