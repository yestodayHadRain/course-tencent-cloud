<?php

namespace App\Http\Desktop\Services;

use App\Exceptions\BadRequest as BadRequestException;
use App\Models\Trade as TradeModel;
use App\Services\Frontend\Trade\TradeCreate as TradeCreateService;
use App\Services\Pay\Alipay as AlipayService;
use App\Services\Pay\Wxpay as WxpayService;

class Trade extends Service
{

    public function create()
    {
        $this->db->begin();

        $service = new TradeCreateService();

        $trade = $service->handle();

        $qrCode = $this->getQrCode($trade);

        if ($trade && $qrCode) {

            $this->db->commit();

            return [
                'sn' => $trade->sn,
                'channel' => $trade->channel,
                'qrcode' => $qrCode,
            ];

        } else {

            $this->db->rollback();

            throw new BadRequestException('trade.create_failed');
        }
    }

    protected function getQrCode(TradeModel $trade)
    {
        $qrCode = null;

        if ($trade->channel == TradeModel::CHANNEL_ALIPAY) {
            $qrCode = $this->getAlipayQrCode($trade);
        } elseif ($trade->channel == TradeModel::CHANNEL_WXPAY) {
            $qrCode = $this->getWxpayQrCode($trade);
        }

        return $qrCode;
    }

    protected function getAlipayQrCode(TradeModel $trade)
    {
        $qrCode = null;

        $service = new AlipayService();

        $text = $service->scan($trade);

        if ($text) {
            $qrCode = $this->url->get(
                ['for' => 'desktop.qrcode'],
                ['text' => urlencode($text)]
            );
        }

        return $qrCode;
    }

    protected function getWxpayQrCode(TradeModel $trade)
    {
        $service = new WxpayService();

        return $service->scan($trade);
    }

}