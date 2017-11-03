<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SalespointController extends Controller
{
	protected $portion;
	protected $chunksize;
	protected $timestamp;

    public function __construct()
    {
    }

    public function handle(Request $request) 
    {

        if(!($p = $request->input("fetch")) || $p == 'ping') {
			return response()->json($this->timestamp());
		}

		$this->portion = $request->input("portion") ?: false;
		$this->chunksize = $request->input("chunksize") ?: 20;
		$this->timestamp = $request->input("timestamp") ?: false;

		$method = 'API_'.preg_replace('/[^0-9a-z_]/', '', $p);

		if (method_exists($this, $method)) {
			return $this->{$method}();
		}

        return response('Not implemented',501)->header('Status','not implemented');
    }

	public function API_orders()
	{
        if(!config('sql.orders.list')) 
            return false;

        if (!$this->portion) {
            $results = app('db')->select(config('sql.orders.count'),[
                'timestamp'=>$this->timestamp,
                'timestamp2'=>$this->timestamp,
            ]);
            return $results[0]->cnt;
        }

        $start = ($this->portion - 1) * $this->chunksize;
        $limit = $this->chunksize;

        $list = app('db')->select(config('sql.orders.list'),[
            'start'=>$start,
            'limit'=>$limit,
			'timestamp'=>$this->timestamp,
			'timestamp2'=>$this->timestamp,
        ]);

        if (!$list) {
            return false;
        }

        $ret = [];
		foreach ($list as $order) {
			$items = array();
            $products = app('db')->select(config('sql.orders.products'),[
                'order_id'=>$order->ID
            ]);
			foreach ($products as $prod) {
				$items[] = array(
					'PRODUCT_ID' => $prod->ID,
					'DISCOUNT' => $prod->DISCOUNT,
					'PRICE' => $prod->PRICE,
					'QUANTITY' => $prod->QUANTITY,
				);
			}

			$promocodes = array();
            if(config('sql.orders.promocodes')) {
                $plist = app('db')->select(config('sql.orders.promocodes'),[
                    'order_id'=>$order->ID
                ]);
                foreach ($plist as $promocode) {
                    $promocodes[] = $promocode->ID;
                }
            }

            $params = array();
            if(config('sql.orders.params')) {
                $plist = app('db')->select(config('sql.orders.params'),[
                    'order_id'=>$order->ID
                ]);
                foreach ($plist as $param) {
                    $params[$param->NAME] = $param->VALUE;
                }
            }

			$customer_cms_id = abs(crc32($order->CUSTOMER_PHONE));

			$ret[] = array(
				'CMS_ID' => $order->ID,
				'DATE_INSERT' => date('Y-m-d H:i:s', strtotime($order->DATE_INSERT)),
				'DATE_UPDATE' => date('Y-m-d H:i:s', strtotime($order->DATE_UPDATE)),
				'STATUS_ID' => $order->STATUS_ID,
				'CUSTOMER_COMMENT' => $order->CUSTOMER_COMMENT,
				'PS_ID' => $order->PS_ID,
				'DELIVERY_ID' => $order->DELIVERY_ID,
				'DELIVERY_PRICE' => $order->DELIVERY_PRICE,
				'DISCOUNT' => $order->DISCOUNT,
				'PROMOCODES' => $promocodes,
				'GOODS' => $items,
				'BUYER_PROPS' => array(
					'CONTACT_PERSON' => $order->CUSTOMER_NAME,
					'PHONE' => $order->CUSTOMER_PHONE,
					'PHONE2' => $order->CUSTOMER_PHONE2,
					'CITY' => $order->CITY,
					'ADDRESS' => $order->ADDRESS,
				),
				'CUSTOMER_PROPS' => array(
					'CMS_ID' => $order->CUSTOMER_ID ?: $customer_cms_id,
					'CONTACT_PERSON' => $order->CUSTOMER_NAME,
					'EMAIL' => $order->CUSTOMER_EMAIL,
					'PHONE' => $order->CUSTOMER_PHONE,
					'CITY' => $order->CITY,
					'ADDRESS' => $order->ADDRESS,
					'FIELDS' => $params,
				),
			);
			$items = array();
		}
        return response()->json($ret);
	}


	public function API_products()
    {
        if(!config('sql.products.list')) 
            return false;

        if (!$this->portion) {
            $results = app('db')->select(config('sql.products.count'));
            return $results[0]->cnt;
        }

        $start = ($this->portion - 1) * $this->chunksize;
        $limit = $this->chunksize;

        $list = app('db')->select(config('sql.products.list'),[
            'start'=>$start,
            'limit'=>$limit,
        ]);

        if (!$list) {
            return false;
        }

        $ret = [];
        foreach($list as $prod) {
            $ret[] = [
                'CMS_ID'=>$prod->ID,
                'CATEGORY_ID'=>$prod->GROUP_ID,
                'GROUP_NAME'=>$prod->GROUP_NAME,
                'MODEL_NAME'=>$prod->MODEL_NAME,
                'VENDOR'=>$prod->VENDOR_NAME,
                'NAME'=>$prod->FULL_NAME,
                'DESCRIPTION'=>$prod->DESCRIPTION,
                'CODE'=>$prod->SKU_CODE,
                'CODE_1C'=>$prod->SKU_CODE_1C,
                'IMAGE'=>$prod->IMAGE,
                'URL'=>$prod->URL,
                'AVAILABILITY'=>$prod->IS_AVAIL,
                'ACTIVE'=>$prod->IS_ACTIVE,
                'PRICES'=>[
                    [
                        'CMS_ID'=>1,
                        'PRICETYPE_ID'=>1,
                        'NAME'=>'Розничная',
                        'PRICE'=>$prod->PRICE,
                    ],
                ],
            ];
        }
        return response()->json($ret);
	}

	public function API_customers()
    {
        if(!config('sql.customers.list')) 
            return false;

        if (!$this->portion) {
            $results = app('db')->select(config('sql.customers.count'),[
                'timestamp'=>$this->timestamp,
                'timestamp2'=>$this->timestamp,
            ]);
            return $results[0]->cnt;
        }

        $start = ($this->portion - 1) * $this->chunksize;
        $limit = $this->chunksize;

        $list = app('db')->select(config('sql.customers.list'),[
            'timestamp'=>$this->timestamp,
            'timestamp2'=>$this->timestamp,
            'start'=>$start,
            'limit'=>$limit,
        ]);

        if (!$list) {
            return false;
        }


        $ret = [];
        foreach($list as $cust) {
            $customer_cms_id = abs(crc32($cust->PHONE));

            $params = array();
            if(config('sql.customers.params')) {
                $plist = app('db')->select(config('sql.customers.params'),[
                    'customer_id'=>$cust->ID
                ]);
                foreach ($plist as $param) {
                    $params[$param->NAME] = $param->VALUE;
                }
            }

			$ret[] = array(
                'CMS_ID' => $cust->ID ?: $customer_cms_id,
				'DATE_INSERT' => date('Y-m-d H:i:s', strtotime($cust->DATE_INSERT)),
				'DATE_UPDATE' => date('Y-m-d H:i:s', strtotime($cust->DATE_UPDATE)),
                'CONTACT_PERSON' => $cust->NAME,
                'EMAIL' => $cust->EMAIL,
                'PHONE' => $cust->PHONE,
                'PHONE2' => $cust->PHONE2,
                'CITY' => $cust->CITY,
                'ADDRESS' => $cust->ADDRESS,
                'FIELDS' => $params,
			);
        }
        return response()->json($ret);
	}

    public function API_order_paysystems()
    {
        if(!config('sql.paysystems.list')) 
            return false;

        $list = app('db')->select(config('sql.paysystems.list'));

        if (!$list) {
            return false;
        }

        $ret = [];
        foreach ($list as $ps) {
            $ret[] = array(
                'CMS_ID' => $ps->ID,
                'NAME' => $ps->NAME,
            );
        }
        return response()->json($ret);
    }

    public function API_order_status()
    {
        if(!config('sql.statuses.list')) 
            return false;

        $list = app('db')->select(config('sql.statuses.list'));

        if (!$list) {
            return false;
        }

        $ret = [];
        foreach ($list as $ps) {
            $ret[] = array(
                'CMS_ID' => $ps->ID,
                'NAME' => $ps->NAME,
            );
        }
        return response()->json($ret);
    }

    public function API_order_delivery()
    {
        if(!config('sql.deliveries.list')) 
            return false;

        $list = app('db')->select(config('sql.deliveries.list'));

        if (!$list) {
            return false;
        }

        $ret = [];
        foreach ($list as $ps) {
            $ret[] = array(
                'CMS_ID' => $ps->ID,
                'NAME' => $ps->NAME,
                'PRICE' => $ps->PRICE,
            );
        }
        return response()->json($ret);
    }


	private function timestamp()
	{
		return time();
	}

}
