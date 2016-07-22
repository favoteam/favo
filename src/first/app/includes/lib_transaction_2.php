<?php

/**
 * ECSHOP 用户交易相关函数库
 * ============================================================================
 * 版权所有 2005-2010 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liuhui $
 * $Id: lib_transaction.php 17063 2010-03-25 06:35:46Z liuhui $
*/

/**
 *  获取用户指定范围的订单列表
 *
 * @access  public
 * @param   int         $user_id        用户ID号
 * @param   int         $num            列表最大数量
 * @param   int         $start          列表起始位置
 * @return  array       $order_list     订单列表
 */
function get_user_orders_2($user_id, $num = 10, $start = 0,$where='')
{
    /* 取得订单列表 */
    $arr    = array();

    $sql = "SELECT o.*, ifnull(ssc.value,'网站自营') as shopname, " .
           "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee ".
           " FROM " .$GLOBALS['ecs']->table('order_info') . ' as o '.
    	   " LEFT JOIN " .$GLOBALS['ecs']->table('supplier_shop_config') . 'as ssc '.
    	   " ON o.supplier_id=ssc.supplier_id AND ssc.code='shop_name' ".
           " WHERE user_id = '$user_id' $where ORDER BY add_time DESC";
    $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);
	
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
		$row['handler'][] = array('name'=>'订单详情','code'=>'order_detail');
		if($row['order_status'] != OS_CANCELED && $row['order_status'] != OS_INVALID && $row['pay_status'] == PS_UNPAYED && !empty($row['pay_id']))
		{
			$row['handler'][] = array('name'=>'立即付款','code'=>'order_detail');
		}
		
        if ($row['order_status'] == OS_UNCONFIRMED)
        {
			$row['handler'][] = array('name'=>$GLOBALS['_LANG']['cancel'],'code'=>'cancel_order');
        }
		
		/* 对配送状态的处理 */
		if ($row['shipping_status'] == SS_SHIPPED)
		{
			$back_num = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('back_order') . " WHERE order_id = " . $row['order_id'] . " AND status_back < 6 AND status_back != 3");
			if ($back_num > 0)
			{
				$back_info = "此单存在正在退货商品，确认收货退货申请将取消。";
			}
			else
			{
				$back_info = "";
			}
			@$okgoods_time = $GLOBALS['db']->getOne("select value from " . $GLOBALS['ecs']->table('shop_config') . " where code='okgoods_time'");
			@$row_time = $okgoods_time - (local_date('d',gmtime()) - local_date('d',$row['shipping_time']));
			@$row['receive_confirm_deadline'] = $row_time;
			$row['handler'][] = array('name'=>$GLOBALS['_LANG']['confirm_received'],'code'=>'affirm_received');
		}
		
        $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
        $row['order_status_text'] = $GLOBALS['_LANG']['os'][$row['order_status']];
		$row['pay_status_text'] = $GLOBALS['_LANG']['ps'][$row['pay_status']];
		$row['shipping_status_text'] = $GLOBALS['_LANG']['ss'][$row['shipping_status']];
		
        $cod_code = $GLOBALS['db']->getOne("select pay_code from " . $GLOBALS['ecs']->table('payment') . " where pay_id=" . $row['pay_id']);
		$row['cod_code'] = $cod_code;
		$weixiu_time = $GLOBALS['db']->getOne("select value from " . $GLOBALS['ecs']->table('shop_config') . " where code='weixiu_time'");
		$row['weixiu_time'] = ($weixiu_time - (local_date('d',gmtime()) - local_date('d',$order['shipping_time_end'])) <= 0) ? 0 : 1;
		
		$back_can_a = 1;
		$comment_s = 0;
		$shaidan_s = 0;
		$goods_list_r = get_order_goods($row);
		foreach($goods_list_r as $g_val)
		{
			if ($g_val['back_can'] == 0)
			{
				$back_can_a = 0;
			}
			if ($g_val['comment_state'] == 0 && $g_val['is_back'] == 0 && $comment_s == 0)
			{
				$comment_s = $g_val['rec_id'];
			}
			if ($g_val['shaidan_state'] == 0 && $g_val['is_back'] == 0 && $shaidan_s == 0)
			{
				$shaidan_s = $g_val['rec_id'];
			}
		}
		if($row['shipping_status'] == SS_RECEIVED)
		{
			if ($comment_s != 0)
			{
				$row['handler'][] = array('name'=>'评价','code'=>'my_comment');
			}
			if($shaidan_s != 0)
			{
				$row['handler'][] = array('name'=>'晒单','code'=>'shaidan_send');
			}
		}
		$extension_code = $row['extension_code'];
		
		/* 预售活动 */
		// if($extension_code == PRE_SALE_CODE)
		// {
		// 	include_once '/includes/lib_goods.php';
		// 	$pre_sale = pre_sale_info($row['extension_id']);
		// 	$pre_sale_status = $pre_sale['status'];
		// 	if($pre_sale['deposit'] > 0)
		// 	{
		// 		$pre_sale_deposit = $pre_sale['deposit'];
		// 		$pre_sale_deposit_format = $pre_sale['formated_deposit'];
		// 	}
		// }
		
		$arr[$row['order_id']] = array('order_id'       => $row['order_id'],
						'order_sn'       => $row['order_sn'],
						'shopname'       => $row['shopname'],
						'order_time'     => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
						'order_status'   => $row['order_status'],
						'pay_status'   => $row['pay_status'],
						'shipping_status'   => $row['shipping_status'],
						'order_status_text'   => $row['order_status_text'],
						'pay_status_text'   => $row['pay_status_text'],
						'shipping_status_text'   => $row['shipping_status_text'],
					    'consignee'   	 => $row['consignee'], //聊天系统-收货人
					    'pay_name'   	 => $row['pay_name'], //聊天系统-支付方式
						'back_can_a'     => $back_can_a,
						'comment_s'      => $comment_s,
						'shaidan_s'      => $shaidan_s,
						'total_fee'      => price_format($row['total_fee'], false),
						'goods_list'     => $goods_list_r,
						'pay_online'       => $row['pay_online'],
						'is_suborder' => $row['parent_order_id'] ? "(子订单)" : "",  //代码增加   By  www.68ecshop.com
						'pay_status'     => $row['pay_status'],
						'handler'        => $row['handler'],
						'shipping_id'    => $row['shipping_id'],
						'shipping_name'  => $row['shipping_name'],
						'shipping_status'=> $row['shipping_status'],
						'pay_id'         => ($cod_code == 'cod' ? '' : $row['pay_id']),
						'invoice_no'     => $row['invoice_no'],
						'extension_code'     => $row['extension_code'], // 用于前台辨识预售活动
						// 'pre_sale_status'     => $pre_sale_status, // 用于前台辨识预售活动状态
						// 'pre_sale_deposit'     => $pre_sale_deposit, // 定金
						// 'pre_sale_deposit_format'     => $pre_sale_deposit_format, // 格式化定金
						'weixiu_time'    => $row['weixiu_time']);
    }

    return $arr;
}


function get_order_goods($order)
{
	
	/* 取得订单商品及货品 */
    $goods_list = array();
    $goods_attr = array();
    $sql = "SELECT o.*, IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, o.goods_attr_id, g.suppliers_id, IFNULL(b.brand_name, '') AS brand_name, p.product_sn, a.attr_value,g.goods_thumb,g.goods_id 
            FROM " . $GLOBALS['ecs']->table('order_goods') . " AS o
                LEFT JOIN " . $GLOBALS['ecs']->table('products') . " AS p
                    ON p.product_id = o.product_id
                LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g
                    ON o.goods_id = g.goods_id
                LEFT JOIN " . $GLOBALS['ecs']->table('brand') . " AS b
                    ON g.brand_id = b.brand_id
				LEFT JOIN " . $GLOBALS['ecs']->table('goods_attr') . " AS a
                    ON o.goods_attr_id = a.goods_attr_id
            WHERE o.order_id = '$order[order_id]'";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        /* 虚拟商品支持 */
        if ($row['is_real'] == 0)
        {
            /* 取得语言项 */
            $filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $_CFG['lang'] . '.php';
            if (file_exists($filename))
            {
                include_once($filename);
                if (!empty($_LANG[$row['extension_code'].'_link']))
                {
                    $row['goods_name'] = $row['goods_name'] . sprintf($_LANG[$row['extension_code'].'_link'], $row['goods_id'], $order['order_sn']);
                }
            }
        }

        $row['formated_subtotal']       = price_format($row['goods_price'] * $row['goods_number']);
        $row['formated_goods_price']    = price_format($row['goods_price']);
	    $row['url'] = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
        $row['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'],true);

        $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

        if ($row['extension_code'] == 'package_buy')
        {
            $row['storage'] = '';
            $row['brand_name'] = '';
            $row['package_goods_list'] = get_package_goods($row['goods_id']);
        }

        $goods_list[] = $row;
    }

	foreach ($goods_list as $goods_key => $goods_val)
	{  
		$sql_goods = "select bo.*,bg.product_id from ". $GLOBALS['ecs']->table('back_order') . " as bo " .
					" left join " . $GLOBALS['ecs']->table('back_goods') . " as bg " .
					" on bo.back_id = bg.back_id and bo.goods_id = bg.goods_id" .
					" where bo.order_id='$order[order_id]' and bo.goods_id='$goods_val[goods_id]' " .
					" and bg.product_id='$goods_val[product_id]' and bo.status_back < 6";
		$back_order =$GLOBALS['db']->getRow($sql_goods);
		$goods_list[$goods_key]['back_can'] =  count($back_order['order_id']) > 0 ? '0' : '1';
		
		switch ($back_order['status_back'])
		{
			case '3' : $sb = "已完成"; break;
			case '5' : $sb = "已申请"; break;
			//case '6' : $sb = ""; break;
			//case '7' : $sb = ""; break;
			default : $sb = "正在"; break;
		}
		
		switch ($back_order['back_type'])
		{
			case '1' : $bt = "退货"; break;
			case '3' : $bt = "申请维修"; break;
			case '4' : $bt = "退款"; break;
			default : break;
		}
		
		$goods_list[$goods_key]['back_can_no'] = $sb . " " . $bt;
	}
	
	return $goods_list;
}
?>