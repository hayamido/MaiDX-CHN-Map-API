<?php
/**
 *  舞萌DX店铺分布可视化地图
 *
 *  @author Hayao Midoriya <zs6096@gmail.com>
 *  @license WTFPL
 *  @description 店铺数据模型类
 *  @package Map\Model
 */

namespace Map\Model;

use \Map\Lib\Curl,
    \Map\Model\DataCompareModel,
    \Think\Exception;

class DataSourceModel {

    /**
     *  店铺数据接口URL存储变量
     *
     *  @var string
     */

    private $_apiUrl;

    /**
     *  店铺地址纠偏数据存储变量
     *
     *  @var array
     */

    private $_locationFix;

    /**
     *  店铺原始数据存储变量
     *
     *  @var array
     */

    private $_rawData = array();

    /**
     *  构造函数
     *
     *  @return void
     */

    public function __construct() {
        $this->_apiUrl = C('API_URL');
        $raw = (new Curl($this->_apiUrl))->get()->result(true);
        $data = array();
        foreach ($raw as $shop) {
            $data[] = array(
                'id'       => $shop['id'],
                'name'     => $shop['arcadeName'],
                'province' => $shop['province'],
                'address'  => $shop['address'],
                'count'    => $shop['machineCount'],
                'lnglat'   => NULL
            );
        }
        $this->_rawData = $data;
        $locFixJson = file_get_contents(SITE_ROOT . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'location_fix.json');
        $this->_locationFix = json_decode($locFixJson, true);
        $this->_fixLocation();
    }

    /**
     *  对店铺位置进行纠偏
     *
     *  @return void
     */

    private function _fixLocation() {
        for ($i = 0; $i < count($this->_rawData); $i++) {
            foreach ($this->_locationFix as $fix) {
                if ($this->_rawData[$i]['id'] == $fix['id']) {
                    $this->_rawData[$i]['lnglat'] = $fix['location'];
                }
            }
        }
    }

    /**
     *  按照店铺名称片段过滤结果
     *
     *  @param string $query 查询字段
     *  @return array
     */

    public function queryShop($query = '') {
        if (empty($query)) {
            return array(
                'count' => count($this->_rawData),
                'rows'  => $this->_rawData
            );
        }
        $result = array();
        foreach ($this->_rawData as $shop) {
            if (strpos($shop['name'], $query) !== false) {
                $result[] = $shop;
            }
            if (strpos($shop['province'], $query) !== false) {
                $result[] = $shop;
            }
        }
        return array(
            'count' => count($result),
            'rows'  => $result
        );
    }

    /**
     *  统计各省份所拥有的机台数量
     *
     *  @return array
     */

    public function getStats() {
        $machineNum = 0;
        foreach ($this->_rawData as $raw) {
            $machineNum += $raw['count'];
        }
        $delta = (new DataCompareModel())->getCompared();
        return array(
            'total' => array(
                'shop'    => count($this->_rawData),
                'machine' => $machineNum
            ),
            'delta' => $delta['count'],
            'shop'  => $delta['data']
        );
    }

}
