<?php
namespace Ign\Bundle\GincoBundle\Repository\Website;

use Ign\Bundle\GincoBundle\Entity\Website\PredefinedRequestCriterion;
use Ign\Bundle\GincoBundle\Entity\Website\PredefinedRequestColumn;
use Ign\Bundle\GincoBundle\Entity\Website\User;
use Doctrine\ORM\QueryBuilder;

/**
 * PredefinedRequestRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PredefinedRequestRepository extends \Doctrine\ORM\EntityRepository {

	/**
	 * Get the list of predefined request (only the description, not the detailed fields and criteria).
	 *
	 * @param String $schema
	 *        	the database schema
	 * @param String $dir
	 *        	the direction of sorting (ASC or DESC)
	 * @param String $sort
	 *        	the sort column
	 * @param String $locale
	 *        	the locale
	 * @param User $user
	 *        	the user
	 * @return Array[PredefinedRequest] the list of requests
	 */
	public function getPredefinedRequestList($schema = 'RAW_DATA', $dir, $sort, $locale, $user) {
		
	    $qb = $this->buildPredefinedRequestListRequest($dir, $sort);
		
		$params = [];
		$params['schema'] = $schema;
		
		$or = $qb->expr()->orx();
		$or->add("pr.isPublic = TRUE");
		if($user->isAllowed('MANAGE_OWNED_PRIVATE_REQUEST')){
		    $or->add("pr.userLogin = :userLogin AND pr.isPublic = FALSE");
		    $params['userLogin'] = $user->getLogin();
		}
		$qb->andWhere($or)->setParameters($params);
		
		return $qb->getQuery()->getResult();
	}

	
	/**
	 * Get the list of editable predefined request (only the description, not the detailed fields and criteria).
	 *
	 * @param String $schema
	 *        	the database schema
	 * @param String $dir
	 *        	the direction of sorting (ASC or DESC)
	 * @param String $sort
	 *        	the sort column
	 * @param String $locale
	 *        	the locale
	 * @param User $user
	 *        	the user
	 * @return Array[PredefinedRequest] the list of requests
	 */
	public function getEditablePredefinedRequestList($schema = 'RAW_DATA', $dir, $sort, $locale, $user) {
        
	    $qb = $this->buildPredefinedRequestListRequest($dir, $sort);
	    
	    $params = [];
	    $params['schema'] = $schema;
	    
	    if(!$user->isAllowed('MANAGE_PUBLIC_REQUEST') && !$user->isAllowed('MANAGE_OWNED_PRIVATE_REQUEST')){
	        return [];
	    }
	    if($user->isAllowed('MANAGE_PUBLIC_REQUEST') && !$user->isAllowed('MANAGE_OWNED_PRIVATE_REQUEST')){
	        $qb->andWhere("pr.isPublic = TRUE");
	    }
	    if(!$user->isAllowed('MANAGE_PUBLIC_REQUEST') && $user->isAllowed('MANAGE_OWNED_PRIVATE_REQUEST')){
	        $qb->andWhere("pr.userLogin = :userLogin AND pr.isPublic = FALSE");
	        $params['userLogin'] = $user->getLogin();
	    }
	    if($user->isAllowed('MANAGE_PUBLIC_REQUEST') && $user->isAllowed('MANAGE_OWNED_PRIVATE_REQUEST')){
	        $or = $qb->expr()->orx();
	        $or->add("pr.isPublic = TRUE");
	        $or->add("pr.userLogin = :userLogin AND pr.isPublic = FALSE");
	        $params['userLogin'] = $user->getLogin();
	        $qb->andWhere($or);
	    }
	    $qb->setParameters($params);
	    
	    return $qb->getQuery()->getResult();
	}
	
	/**
	 * Get the query builder for the list of predefined request.
	 *
	 * @param String $dir
	 *        	the direction of sorting (ASC or DESC)
	 * @param String $sort
	 *        	the sort column
	 * @return QueryBuilder the query builder
	 */
	protected function buildPredefinedRequestListRequest($dir, $sort) {
	    
	    // Translate the columns names
	    $columnNames = array(
	        'request_id' => 'pr.requestId',
	        'label' => 'pr.label',
	        'definition' => 'pr.definition',
	        'date' => 'pr.date',
	        'position' => 'prga.position',
	        'is_public' => 'pr.isPublic',
	        'group_id' => 'prg.groupId',
	        'group_label' => 'prg.label',
	        'group_position' => 'prg.position',
	        'dataset_id' => 'ds.datasetId',
	        'dataset_label' => 'ds.label'
	    );
	    if (in_array($sort, $columnNames, true)) {
	        $sort = $columnNames[$sort];
	    } else {
	        $sort = $columnNames['label'];
	    }
	    $dirs = array(
	        'ASC',
	        'DESC'
	    );
	    if (!in_array($dir, $dirs, true)) {
	        $dir = $dirs[0];
	    }
	    
	    $qb = $this->_em->createQueryBuilder();
	    $qb->select('pr, ds, prga, prg')
	    ->from('IgnGincoBundle:Website\PredefinedRequest', 'pr')
	    ->join('pr.datasetId', 'ds')
	    ->leftJoin('pr.groups', 'prga')
	    ->leftJoin('prga.groupId', 'prg')
	    ->where('pr.schemaCode = :schema')
	    ->orderBy($sort, $dir);

	    return $qb;
	}
	
	/**
	 * Get a predefined request.
	 *
	 * @param String $requestId
	 *        	the id of the request
	 * @param String $locale
	 *        	the locale
	 * @return PredefinedRequest the request
	 */
	public function getPredefinedRequest($requestId, $locale) {
		$qb = $this->_em->createQueryBuilder();
		$qb->select('pr, ds, prga, prg')
			->from('IgnGincoBundle:Website\PredefinedRequest', 'pr')
			->join('pr.datasetId', 'ds')
			->leftjoin('pr.groups', 'prga')
			->leftjoin('prga.groupId', 'prg')
			->where('pr.requestId = :requestId')
			->setParameters([
			'requestId' => $requestId
		]);
		
		$request = $qb->getQuery()->getSingleResult();
		
		// Get the request columns
		$pRColumnRepository = $this->_em->getRepository(PredefinedRequestColumn::class);
		$request->setColumns($pRColumnRepository->getPredefinedRequestColumns($requestId, $locale));
		
		// Get the request criteria
		$pRCriterionRepository = $this->_em->getRepository(PredefinedRequestCriterion::class);
		$request->setCriteria($pRCriterionRepository->getPredefinedRequestCriteria($requestId, $locale));
		
		return $request;
	}
}