<?php
/**
 * 高德定位模块
 * Created by PhpStorm.
 * User: guoxinqiang
 * Date: 17-3-14
 * Time: 下午3:02
 */
namespace Lbs\Model;
use Think\Model;

class lbsModel extends Model{

    protected $autoCheckFields = false;

    private static $key = '';

    private static $protocol = '';

    private static $locationMode = array();

    private static $interFaceLink = array();

    private static $locationUrl = '';

    private static $interfaceOutPut = '';

    private static $outPut='array';

    private static $staticError = '';
    public function _initialize(){
        self::$protocol = 'http://';
        self::$interFaceLink['ip'] = 'restapi.amap.com/v3/ip?';
        self::$interFaceLink['coordinate'] = 'restapi.amap.com/v3/geocode/regeo?';
        self::$interFaceLink['district'] = 'restapi.amap.com/v3/config/district';
        self::$key ='1d6b5bc6335ea1cb8b3f8851f6a5c3f5';
        self::$interfaceOutPut = 'json';
    }


    /**
     * 获取当前的位置
     * 优化高德定位方法
    */
    public static function getLocation(){
      static $response = array();
      $locationMode = self::$locationMode;
      /**
       * 单个定位
      */
      if(count($locationMode)<=1){
          /**ip地址定位*/
          if(array_key_exists('ipAddress',$locationMode)){
              $ipAddressHistory = &$response['ipAddress'];
              $ipAddress = self::$locationMode['ipAddress'];
              if(array_key_exists($ipAddress,$ipAddressHistory)){
                 return $ipAddressHistory[$ipAddress];
              }
              self::$locationUrl = self::$interFaceLink['ip'].'ip='.$locationMode['ipAddress'];
              $ipAddressHistory[$ipAddress] = self::accessInterface();
              return $response;
          }

          /**坐标定位*/
          if(array_key_exists('coordinate',$locationMode)){
              $coordinateHistory = &$response['coordinate'];
              $coordinate = $locationMode['coordinate'];
              if(empty($coordinate)){
                  self::setStaticError('没有坐标地址！');
                  return false;
              }
              if(array_key_exists($coordinate,$coordinateHistory)){
                  return $coordinateHistory[$coordinate];
              }
              self::$locationUrl = self::$interFaceLink['coordinate'].'location='.$locationMode['coordinate'];
              $coordinateHistory[$coordinate] = self::accessInterface();
              return $coordinateHistory;
          }


      }

    }




    /**
     * 获取行政区
    */
    public static function getDistrict(){
        static $response=array();
        $locationMode = array_filter(self::$locationMode['dirstrict']);
        $url = self::$interFaceLink['district'];
        //关键字是否存在
        if(!array_key_exists('keywords',$locationMode)){
            self::setStaticError('关键字不存在！');
        }
        //拼接url
        self::$locationUrl = stitching_parameter($url,$locationMode);
        //访问接口进行获取数据
        return self::accessInterface();
    }


    /**
     * 设置静态错误
    */
    public static function setStaticError($err){

        return !APP_DEBUG ? self::$staticError=$err : E($err);

    }

    /**
     * 获取静态的错误
    */
    public static function getStaticError(){

        return self::$staticError;

    }




    /**
     * 设置ip地址
    */
    public static function setIpAddress($ipAddress=null){

        if(empty($ipAddress)){
            //获取ip地址
            $ipAddress = get_client_ip();
        }
        self::$locationMode['ipAddress'] = $ipAddress;
        
    }


    /**设置地理编码坐标*/
    public static function setCoordinate($coordinate){

        self::$locationMode['coordinate'] = $coordinate;

    }



    /**
     * 设置关键字
     * 查询行政区的时候使用的
     */
    public static function setKeywords($keywords){

        self::$locationMode['dirstrict']['keywords']= $keywords;

    }


    /*
     * 设置子类级别行政区
     * 查询行政区的时候使用的
     * **/
    public static function setSubDistrict($substrict=0){

        self::$locationMode['dirstrict']['subdistrict'] = $substrict;

    }

    /**
     * 显示第几页面
     * 查询行政区的时候使用的
    */
    public static function setPage($page=1){

        self::$locationMode['dirstrict']['page'] = $page;

    }


    /**
     *最外层返回数据个数
     * 查询行政区的时候使用的
    */
    public static function setOffset($offset=20){

        self::$locationMode['dirstrict']['offset'] = $offset;

    }


    /**
     * 返回控制结果
     * 查询行政区的时候使用的
     * base|all
    */
    public static function setExtensions($extension='base'){

        self::$locationMode['dirstrict']['extensions'] = $extension;

    }


    /**
     * 过滤结果
     *查询行政区的时候使用的
    */
    public static function setFilter($filter=''){

        self::$locationMode['dirstrict']['filter'] = $filter;

    }
    /**
     * 是否显示商圈
     * 查询行政区的时候使用的
    */
    public function setShowBiz($showbiz=1){

        self::$locationMode['dirstrict']['subdistrict'] = $showbiz;

    }

    /**
     * 设置返回的方式
     * native|array
     * native原生
     * array数组（非对象）
    */
    public static function setOutPut($output='array'){

       self::$outPut = $output;

    }

    /**设置原生返回接口文档*/
    public static function setInterfaceOutPut($interfaceOutPut='json'){
        self::$interfaceOutPut = $interfaceOutPut;
    }

    /**访问接口*/
    private static function accessInterface(){

        $url = self::$protocol.self::$locationUrl.'&key='.self::$key.'&output='.self::$interfaceOutPut;
        $curl = curl_init($url);
        $curlOpt[CURLOPT_RETURNTRANSFER] = true;
        $curlOpt[CURLOPT_HEADER] = false;
        $curlOpt[CURLOPT_ENCODING] = 'utf8';
        $curlOpt[CURLOPT_CONNECTTIMEOUT]=120;
        $curlOpt[CURLOPT_SSL_VERIFYHOST]=0;
        $curlOpt[CURLOPT_SSL_VERIFYPEER]=false;
        curl_setopt_array($curl,$curlOpt);
        $response= curl_exec($curl);
        curl_close($curl);
        /**判断输出方式*/
        switch(self::$outPut){
            //显示原本的样式
            case 'native':
                echo '不是数组';
                return $response;
                break;
            case 'array':
                //如果是xml对象
                if(is_xml($response)){
                    return xml_string_parse($response);

                }
                /**如果是json对象*/
                if(is_json($response)){
                    return json_decode($response,true);
                }
        }
    }


    public static function Location(){

    }


}