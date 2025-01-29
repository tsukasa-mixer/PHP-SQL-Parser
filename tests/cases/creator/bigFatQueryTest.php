<?php

namespace PHPSQLParser\Test\Creator;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

class bigFatQueryTest extends \PHPUnit\Framework\TestCase
{
    protected function innertTestSql($sql)
    {
        $parser = new PHPSQLParser();
        $creator = new PHPSQLCreator();

        $parser->parse($sql, true);

        $this->assertEquals($sql, $creator->create($parser->parsed));
    }


    protected function getSql()
    {
        return <<<SQL
SELECT *, producers.prd_id AS prd_id, IF(prd_info IS NOT NULL,'<img src="/_sysimg/yes.gif" alt="есть" title="есть">',NULL) AS info_sign, IF(prd_concern_prd_id IS NULL,'',(SELECT prd_name FROM producers AS p1 WHERE p1.prd_id = producers.prd_concern_prd_id)) AS prd_concern_prd_name FROM `producers` LEFT JOIN countries ON prd_cnt_id = cnt_id INNER JOIN producer_names pn ON producers.prd_id = pn.prd_id WHERE (prd_name LIKE '%Трям.%' OR prd_full_name LIKE '%Трям.%' OR pn.name LIKE '%Трям.%') GROUP BY producers.prd_id UNION SELECT *, producers.prd_id AS prd_id, IF(prd_info IS NOT NULL,'<img src="/_sysimg/yes.gif" alt="есть" title="есть">',NULL) AS info_sign, IF(prd_concern_prd_id IS NULL,'',(SELECT prd_name FROM producers AS p1 WHERE p1.prd_id = producers.prd_concern_prd_id)) AS prd_concern_prd_name FROM `producers` LEFT JOIN countries ON prd_cnt_id = cnt_id INNER JOIN producer_names pn ON producers.prd_id = pn.prd_id WHERE (prd_name LIKE '%Трям.%' OR prd_full_name LIKE '%Трям.%' OR pn.name LIKE '%Трям.%') GROUP BY producers.prd_id
SQL;
    }


    protected function getSql2()
    {
        return <<<SQL
SELECT *, COALESCE(g.description,'- все -') AS `group`, COALESCE(stc_name,'- все -') AS `stc_name`, COALESCE(NULLIF(CONCAT_WS(' / ',u.`login`,NULLIF(u.`description`,'')),''),'- любой -') AS user, COALESCE(cgr.cgr_name,'- все -') AS `client_group`, IFNULL(categ_client.name,'- любая -') AS `client_category`, IF(cpm_cst_id = '%','- любой -',IF(customers.company IS NULL OR customers.company = '',CONCAT_WS(' ',customers.contact_surname,customers.contact_first_name,customers.contact_patronymic_name),customers.company)) AS client_id, COALESCE(os1.stt_name,'- все -') AS stt_name1, COALESCE(os2.stt_name,'- все -') AS stt_name2, IF(cpo_provider = '%','- все -',COALESCE(prv.short_name,'- неизвестный поставщик -')) AS prv_name, IF(clm_caption = '%','любая',clm_caption) AS clm_caption, IFNULL(IF(cpo_value = '%','любая',clm_name),cpo_value) AS clm_name, IF(cpo_set = '%','любой',ccs.set_description) AS set_description, cfg_permission_options.fl_del AS fl_del FROM `cfg_permission_options` INNER JOIN s_ar_jat_w6_feature_4286_modify_translation_fields.cfg_permission_models ON cpm_id = cpo_cpm_id LEFT JOIN _groups g ON cpm_group_id = g.group_id LEFT JOIN stocks s ON cpm_stc_id = s.stc_id LEFT JOIN _users u ON cpm_login = u.login LEFT JOIN client_groups cgr ON cpm_cst_cgr_id = cgr.cgr_id LEFT JOIN s_autoprice_jat_w6_feature_4286_modify_translation_fields.category_client categ_client ON cpm_categ_client_id = categ_client_id LEFT JOIN customers ON cpm_cst_id = cst_id LEFT JOIN order_states os1 ON cpo_state_id = os1.stt_id LEFT JOIN order_states os2 ON cpo_new_state_id = os2.stt_id LEFT JOIN s_autoprice_jat_w6_feature_4286_modify_translation_fields.provider prv ON prv.provider_id = cpo_provider LEFT JOIN cfg_column_sets ccs ON ccs.set_id = cpo_set LEFT JOIN cfg_columns ON clm_name = cpo_value AND clm_set = cpo_set WHERE cpo_type = '6' GROUP BY cpm_id ORDER BY cpo_priority ASC
SQL;
    }

    protected function getSql3()
    {
        return <<<SQL
SELECT *, GROUP_CONCAT(DISTINCT IF(sm1.stm_id IS NULL,'удалённый менеджер(ы)',CONCAT_WS(' / ',u1.login,u1.fullname)) SEPARATOR '<br />') AS old_manager, GROUP_CONCAT(DISTINCT IF(sm2.stm_id IS NULL,'удалённый менеджер(ы)',CONCAT_WS(' / ',u2.login,u2.fullname)) SEPARATOR '<br />') AS new_manager FROM `_change_manager_log` INNER JOIN _change_manager_customers ON cmc_cml_id = cml_id LEFT JOIN stock_managers sm1 ON sm1.stm_id = cmc_old_stm_id LEFT JOIN _users u1 ON u1.login = sm1.stm_user_login LEFT JOIN stock_managers sm2 ON sm2.stm_id = cmc_new_stm_id LEFT JOIN _users u2 ON u2.login = sm2.stm_user_login GROUP BY cml_id ORDER BY cml_datetime DESC
SQL;
    }

    protected function getSql4()
    {
        return <<<SQL
SELECT *, /* * crt_rate*/ pst_price AS pst_price FROM table
SQL;
    }

    protected function getSql5()
    {
        return <<<SQL
SELECT * FROM table WHERE (
#на случай, если клиент не пользуется отгрузками и возвратами, а только оперирует состояниями
 IFNULL(pd1.pst_delivered,0) = 0) = FALSE GROUP BY rrt_id
SQL;
    }

    protected function getSql6()
    {
        return <<<SQL
SELECT (rpt_id LIKE '%backup') AS backup FROM table
SQL;
    }


    public function testBig()
    {
        $this->innertTestSql(
            $this->getSql()
        );
    }
    public function testBig2()
    {
        $this->innertTestSql(
            $this->getSql2()
        );
    }

    public function testBig3()
    {
        $this->innertTestSql(
            $this->getSql3()
        );
    }

    public function testBig4()
    {
        $this->innertTestSql(
            $this->getSql4()
        );
    }

    public function testBig5()
    {
        $this->innertTestSql(
            $this->getSql5()
        );
    }
    public function testBig6()
    {
        $this->innertTestSql(
            $this->getSql6()
        );
    }
}
