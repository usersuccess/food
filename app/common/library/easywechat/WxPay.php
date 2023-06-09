<?php

namespace app\common\library\easywechat;

use app\api\service\order\paysuccess\type\PayTypeSuccessFactory;
use app\common\enum\order\OrderTypeEnum;
use app\common\enum\order\OrderPayTypeEnum;
use app\common\exception\BaseException;

/**
 * 微信支付
 */
class WxPay
{
    // 微信支付配置
    private $app;

    /**
     * 构造函数
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 统一下单API
     */
    public function unifiedorder($order_no, $openid, $totalFee, $orderType = OrderTypeEnum::MASTER, $pay_source)
    {
        $data = [
            'attach' => json_encode(['order_type' => $orderType, 'pay_source' => $pay_source]),
            'body' => $order_no,
            'out_trade_no' => $order_no,
            'total_fee' => $totalFee * 100,// 价格:单位分
            'spbill_create_ip' => \request()->ip(),
            'notify_url' => base_url() . 'index.php/job/notify/wxpay',  // 异步通知地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $openid
        ];
        //h5支付差异
        if ($pay_source == 'payH5') {
            unset($data['openid']);
            $data['trade_type'] = 'MWEB';
            $data['scene_info'] = '{"h5_info":{"type":"Wap","wap_url":' . base_url() . ',"wap_name":"支付"}}';//场景信息 必要参数
        }
        if ($pay_source == 'app') {
            $data['trade_type'] = 'APP';
            $data['body'] = '订单支付';
        }
        // 统一下单
        $result = $this->app->order->unify($data);
        // 请求失败
        if ($result['return_code'] === 'FAIL') {
            throw new BaseException(['msg' => "微信支付api：{$result['return_msg']}", 'code' => -3]);
        }
        if ($result['result_code'] === 'FAIL') {
            throw new BaseException(['msg' => "微信支付api：{$result['err_code_des']}", 'code' => -3]);
        }
        //如果是微信小程序
        if ($pay_source == 'wx' || $pay_source == 'app') {
            $time = time();
            if ($pay_source == 'wx') {
                // 二次签名的参数必须与下面相同
                $params = [
                    'appId' => $result['appid'],//有所修改
                    'timeStamp' => $time,
                    'nonceStr' => $result['nonce_str'],
                    'package' => 'prepay_id=' . $result['prepay_id'],
                    'signType' => 'MD5',
                ];
                $result['paySign'] = $this->makeSign($params);
                return [
                    'prepay_id' => $result['prepay_id'],
                    'nonceStr' => $result['nonce_str'],
                    'timeStamp' => (string)$time,
                    'paySign' => $result['paySign']
                ];
            } else if ($pay_source == 'app') {
                // 二次签名的参数必须与下面相同
                $params = [
                    'appid' => $result['appid'],//有所修改
                    'partnerid' => $result['mch_id'],
                    'prepayid' => $result['prepay_id'],
                    'package' => 'Sign=WXPay',
                    'noncestr' => $result['nonce_str'],
                    'timestamp' => $time,
                ];
                $result['paySign'] = $this->makeSign($params);
                return [
                    'appid' => $result['appid'],
                    'partnerid' => $result['mch_id'],
                    'prepayid' => $result['prepay_id'],
                    'package' => 'Sign=WXPay',
                    'noncestr' => $result['nonce_str'],
                    'timestamp' => (string)$time,
                    'sign' => $result['paySign']
                ];
            }
        }
        return $result;
    }

    /**
     * 支付成功异步通知
     */
    public function notify()
    {
        if (!$xml = file_get_contents('php://input')) {
            log_write('Not found DATA');
            $this->returnCode(false, 'Not found DATA');
        }
        // 将服务器返回的XML数据转化为数组
        $data = $this->fromXml($xml);
        // 记录日志
        log_write($xml);
        log_write($data);
        $attach = json_decode($data['attach'], true);
        // 实例化订单模型
        $PaySuccess = PayTypeSuccessFactory::getFactory($data['out_trade_no'], $attach);
        $app_id = $PaySuccess->isExist();
        $app_id == 0 && $this->returnCode(false, '订单不存在');
        // 支付配置信息
        if ($attach['pay_source'] == 'mp' || $attach['pay_source'] == 'payH5') {
            $this->app = AppMp::getWxPayApp($app_id);
        } else if ($attach['pay_source'] == 'wx') {
            $this->app = AppWx::getWxPayApp($app_id);
        } else if ($attach['pay_source'] == 'app') {
            $this->app = AppOpen::getWxPayApp($app_id);
        }
        // 保存微信服务器返回的签名sign
        $dataSign = $data['sign'];
        // sign不参与签名算法
        unset($data['sign']);
        // 生成签名
        $sign = $this->makeSign($data);
        // 判断签名是否正确 判断支付状态
        if (
            ($sign !== $dataSign)
            || ($data['return_code'] !== 'SUCCESS')
            || ($data['result_code'] !== 'SUCCESS')
        ) {
            $this->returnCode(false, '签名失败');
        }
        // 订单支付成功业务处理
        $status = $PaySuccess->onPaySuccess(OrderPayTypeEnum::WECHAT, $data);
        if ($status == false) {
            $this->returnCode(false, $PaySuccess->error);
        }
        // 返回状态
        $this->returnCode(true, 'OK');
    }

    /**
     * 申请退款API
     */
    public function refund($transaction_id, $total_fee, $refund_fee)
    {
        // 当前时间
        $time = time();
        $result = $this->app->refund->byTransactionId($transaction_id, $time, intval($total_fee * 100), intval($refund_fee * 100), [
            // 可在此处传入其他参数，详细参数见微信支付文档
            'refund_desc' => '用户申请取消',
        ]);

        // 请求失败
        if (empty($result)) {
            throw new BaseException(['msg' => '微信退款api请求失败']);
        }
        // 请求失败
        if ($result['return_code'] === 'FAIL') {
            throw new BaseException(['msg' => 'return_msg: ' . $result['return_msg']]);
        }
        if ($result['result_code'] === 'FAIL') {
            throw new BaseException(['msg' => 'err_code_des: ' . $result['err_code_des']]);
        }
        return true;
    }

    /**
     * 企业付款到零钱API
     */
    public function transfers($order_no, $openid, $amount, $desc)
    {
        $result = $this->app->transfer->toBalance([
            'partner_trade_no' => $order_no, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $openid,
            'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            'amount' => $amount * 100, // 企业付款金额，单位为分
            'desc' => $desc, // 企业付款操作说明信息。必填
        ]);
        // 请求失败
        if (empty($result)) {
            throw new BaseException(['msg' => '微信提现到零钱api请求失败']);
        }
        // 请求失败
        if ($result['return_code'] === 'FAIL') {
            throw new BaseException(['msg' => 'return_msg: ' . $result['return_msg']]);
        }
        if ($result['result_code'] === 'FAIL') {
            throw new BaseException(['msg' => 'err_code_des: ' . $result['err_code_des']]);
        }
        return true;
    }

    /**
     * 返回状态给微信服务器
     */
    private function returnCode($returnCode = true, $msg = null)
    {
        // 返回状态
        $return = [
            'return_code' => $returnCode ? 'SUCCESS' : 'FAIL',
            'return_msg' => $msg ?: 'OK',
        ];
        // 记录日志
        log_write([
            'describe' => '返回微信支付状态',
            'data' => $return
        ]);
        die($this->toXml($return));
    }


    /**
     * 生成签名
     */
    private function makeSign($values)
    {
        //签名步骤一：按字典序排序参数
        ksort($values);
        $string = $this->toUrlParams($values);
        //签名步骤二：在string后加入KEY
        $string = $string . '&key=' . $this->app->config['key'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 格式化参数格式化成url参数
     */
    private function toUrlParams($values)
    {
        $buff = '';
        foreach ($values as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $buff .= $k . '=' . $v . '&';
            }
        }
        return trim($buff, '&');
    }

    /**
     * 将xml转为array
     */
    private function fromXml($xml)
    {
        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }


    /**
     * 输出xml字符
     * @param $values
     * @return bool|string
     */
    private function toXml($values)
    {
        if (!is_array($values)
            || count($values) <= 0
        ) {
            return false;
        }

        $xml = "<xml>";
        foreach ($values as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
}
