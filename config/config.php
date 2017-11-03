<?php

config([
    'token' => '95d145eb654a61b903a2ec4c9be821c3',
    'sql'=>[
        'products'=>[
            'count'=>'SELECT COUNT(*) AS cnt FROM bask_prod',
            'list'=>"
				SELECT 
					p.id AS ID,
					IF(p.catalog_id,p.catalog_id,pr.catalog_id) AS GROUP_ID,
					IF(p.`5`,p.`5`,pr.`5`) AS GROUP_NAME,
					IF(p.name,p.name,pr.name) AS MODEL_NAME,
					IF(p.manuf_id,UPPER(p.manuf_id),UPPER(pr.manuf_id)) AS VENDOR_NAME,
					IF(p.name,p.name,pr.name) AS FULL_NAME,
					IF(p.description,p.description,pr.description) AS DESCRIPTION,
					UPPER(p.art) AS SKU_CODE,
					'' AS SKU_CODE_1C,
					'' AS IMAGE,
					'' AS URL,
					0 AS IS_AVAIL,
					0 AS IS_ACTIVE,
					p.selling_price AS PRICE
				FROM bask_prod p
				INNER JOIN bask_prod pr ON (p.parent_id = pr.id)
				LIMIT :start,:limit
            ",
        ],
        'orders'=>[
            'count'=>'SELECT COUNT(*) AS cnt FROM mg_zakaz_all WHERE site_id = 3 AND IF(:timestamp,UNIX_TIMESTAMP(DATA)>:timestamp2,1)',
            'list'=>"
				SELECT 
					id AS ID,
                    DATA AS DATE_INSERT,
                    DATA AS DATE_UPDATE,
                    STATUS AS STATUS_ID,
                    CONCAT(coment,' ',time) AS CUSTOMER_COMMENT,
                    1 AS PS_ID,
                    delivery AS DELIVERY_ID,
                    delivery_cost AS DELIVERY_PRICE,
                    discount AS DISCOUNT,
                    name AS CUSTOMER_NAME,
                    tel AS CUSTOMER_PHONE,
                    tel2 AS CUSTOMER_PHONE2,
                    toun AS CITY,
                    adress AS ADDRESS,
                    '' AS CUSTOMER_ID,
                    email AS CUSTOMER_EMAIL
				FROM mg_zakaz_all
                WHERE IF(:timestamp,UNIX_TIMESTAMP(DATA)>:timestamp2,1)
				LIMIT :start,:limit
            ",
            'products'=>"
				SELECT
					p_id AS PRODUCT_ID,
					0 AS DISCOUNT,
					price as PRICE,
					num as QUANTITY
				FROM
					mg_bsk
				WHERE z_id = :order_id
            ",
        ],
        'customers'=>[
            'count'=>'SELECT COUNT(cnt) AS cnt FROM (SELECT COUNT(*) AS cnt FROM mg_zakaz_all WHERE site_id = 3 AND IF(:timestamp,UNIX_TIMESTAMP(DATA)>:timestamp2,1) GROUP BY trim(lower(email))) a',
            'list'=>"
                SELECT 
                    '' AS ID,
                    DATA AS DATE_INSERT,
                    DATA AS DATE_UPDATE,
                    TRIM(name) AS NAME,
                    TRIM(tel) AS PHONE,
                    TRIM(tel2) AS PHONE2,
                    toun AS CITY,
                    adress AS ADDRESS,
                    email AS EMAIL
                FROM mg_zakaz_all
                WHERE IF(:timestamp,UNIX_TIMESTAMP(DATA)>:timestamp2,1)
                GROUP BY trim(lower(email))
				LIMIT :start,:limit
            ",
        ],
        'paysystems'=>[
            'list'=>"
                SELECT
                    1 AS ID,
                    'Наличные' AS NAME
            ",
        ],
        'statuses'=>[
            'list'=>"
                SELECT
                    1 AS ID,
                    'Новый' AS NAME
            ",
        ],
        'deliveries'=>[
            'list'=>"
	            SELECT 
	                id AS ID,
                    name AS NAME,
                    sum AS PRICE
	            FROM `mg_delivery`
            ",
        ],
    ],
]);
