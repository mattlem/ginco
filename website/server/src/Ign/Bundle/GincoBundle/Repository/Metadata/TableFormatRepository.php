<?php
namespace Ign\Bundle\GincoBundle\Repository\Metadata;

use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * TableFormatRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TableFormatRepository extends \Doctrine\ORM\EntityRepository {

	/**
	 * Get the information about a table format.
	 *
	 * @param String $schema
	 *        	the schema identifier
	 * @param String $format
	 *        	the format
	 * @param String $lang
	 *        	the lang
	 * @return Application_Object_Metadata_TableFormat
	 */
	public function getTableFormat($schema, $format, $lang) {
		$rsm = new ResultSetMappingBuilder($this->_em);
		$rsm->addRootEntityFromClassMetadata($this->_entityName, 't');
		
		// Get the fields specified by the format
		$sql = "SELECT format, schema_code, table_name, COALESCE(t.label, tf.label) as label, primary_key ";
		$sql .= " FROM table_format tf";
		$sql .= " LEFT JOIN translation t ON (lang = ? AND table_format = 'TABLE_FORMAT' AND row_pk = format)";
		$sql .= " WHERE schema_code = ? ";
		$sql .= " AND format = ? ";
		
		$query = $this->_em->createNativeQuery($sql, $rsm);
		$query->setParameters(array(
			$lang,
			$schema,
			$format
		));
		
		return $query->getResult()[0];
	}
	
	/**
	 * Get all table formats.
	 *
	 * @return Array[TableFormat]
	 */
	public function getAllTableFormats() {
		$rsm = new ResultSetMappingBuilder($this->_em);
		$rsm->addRootEntityFromClassMetadata($this->_entityName, 't');
	
		$sql = "SELECT format, table_name, schema_code, primary_key, label, definition ";
		$sql .= " FROM metadata.table_format;";
		
		$query = $this->_em->createNativeQuery($sql, $rsm);
		
		return $query->getResult();
	}
}
