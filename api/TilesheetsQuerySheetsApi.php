<?php

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use Wikimedia\Rdbms\ILoadBalancer;

class TilesheetsQuerySheetsApi extends ApiQueryBase {
	public function __construct(
		$query,
		$moduleName,
		private ILoadBalancer $loadBalancer
	) {
		parent::__construct( $query, $moduleName, 'ts' );
	}

    public function getAllowedParams() {
        return array(
            'limit' => array(
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
            ),
            'from' => array(
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_DEFAULT => '',
            ),
        );
    }

    public function getExamples() {
        return array(
            'api.php?action=query&list=tilesheets&tslimit=50',
            'api.php?action=query&list=tilesheets&tsfrom=Q',
        );
    }

    public function execute() {
		$limit = (int)$this->getParameter( 'limit' );
		$from = $this->getParameter( 'from' );
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );

        $results = $dbr->select(
            'ext_tilesheet_images',
            '*',
            array("`mod` BETWEEN {$dbr->addQuotes($from)} AND 'zzzzzzzz'"),
            __METHOD__,
            array(
                'ORDER BY' => '`mod` ASC',
                'LIMIT' => $limit + 1,
            )
        );

        $ret = array();
        $count = 0;
        foreach ($results as $res) {
            $count++;
            if ($count > $limit) {
                $this->setContinueEnumParameter('from', $res->mod);
                break;
            }
            $sizes = array_map('intval', explode(',', $res->sizes));
            array_push($ret, array('mod' => $res->mod, 'sizes' => $sizes));
        }

        $this->getResult()->addValue('query', 'tilesheets', $ret);
    }
}
