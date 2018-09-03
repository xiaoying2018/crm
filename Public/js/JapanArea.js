var provinces = [
        {'id':1,'pid':0,'name':'北海道地区','level':1},
        {'id':2,'pid':0,'name':'东北地区','level':1},
        {'id':3,'pid':0,'name':'中国地区','level':1},
        {'id':4,'pid':0,'name':'四国地区','level':1},
        {'id':5,'pid':0,'name':'九州地区','level':1},
        {'id':6,'pid':0,'name':'冲绳地区','level':1},
        {'id':10,'pid':0,'name':'关东地区','level':1},
        {'id':7,'pid':0,'name':'关西地区','level':1},
        {'id':8,'pid':0,'name':'中部地区','level':1},
        {'id':9,'pid':0,'name':'北陆地区','level':1},
    ],
    citys = new Array();
// 北海道地区
// citys[1] = [];
// 东北地区
citys[2] = [
    {'id':34,'pid':2,'name':'青森县','level':2},
    {'id':35,'pid':2,'name':'秋田县','level':2},
    {'id':36,'pid':2,'name':'山形县','level':2},
    {'id':37,'pid':2,'name':'岩手县','level':2},
    {'id':38,'pid':2,'name':'宫城县','level':2},
    {'id':39,'pid':2,'name':'福岛县','level':2},
];
// 中国地区
citys[3] = [
    {'id':40,'pid':3,'name':'广岛县','level':2},
    {'id':41,'pid':3,'name':'冈山县','level':2},
    {'id':42,'pid':3,'name':'山口县','level':2},
    {'id':43,'pid':3,'name':'岛根县','level':2},
    {'id':44,'pid':3,'name':'鸟取县','level':2},
];
// 四国地区
citys[4] = [
    {'id':45,'pid':4,'name':'德岛县','level':2},
    {'id':46,'pid':4,'name':'香川县','level':2},
    {'id':47,'pid':4,'name':'爱媛县','level':2},
    {'id':48,'pid':4,'name':'高知县','level':2},
];
// 九州地区
citys[5] = [
    {'id':49,'pid':5,'name':'福冈县','level':2},
    {'id':50,'pid':5,'name':'佐贺县','level':2},
    {'id':51,'pid':5,'name':'长崎县','level':2},
    {'id':52,'pid':5,'name':'熊本县','level':2},
    {'id':53,'pid':5,'name':'大分县','level':2},
    {'id':54,'pid':5,'name':'宫崎县','level':2},
    {'id':55,'pid':5,'name':'鹿儿岛','level':2},

];
// 冲绳地区
citys[6] = [
    {'id':56,'pid':6,'name':'冲绳','level':2},
];
// 关东地区
citys[10] = [
    {'id':11,'pid':10,'name':'东京都','level':2},
    {'id':12,'pid':10,'name':'神奈川','level':2},
    {'id':13,'pid':10,'name':'千叶县','level':2},
    {'id':14,'pid':10,'name':'群马县','level':2},
    {'id':15,'pid':10,'name':'栃木县','level':2},
    {'id':16,'pid':10,'name':'茨城县','level':2},
    {'id':17,'pid':10,'name':'埼玉县','level':2},
];
// 关西地区
citys[7] = [
    {'id':18,'pid':7,'name':'京都府','level':2},
    {'id':19,'pid':7,'name':'大阪府','level':2},
    {'id':20,'pid':7,'name':'兵库县','level':2},
    {'id':21,'pid':7,'name':'滋贺县','level':2},
    {'id':22,'pid':7,'name':'奈良县','level':2},
    {'id':23,'pid':7,'name':'和歌山','level':2},
];
// 中部地区
citys[8] = [
    {'id':24,'pid':8,'name':'爱知县','level':2},
    {'id':25,'pid':8,'name':'三重县','level':2},
    {'id':26,'pid':8,'name':'岐阜县','level':2},
    {'id':27,'pid':8,'name':'静冈县','level':2},
    {'id':28,'pid':8,'name':'山梨县','level':2},
    {'id':29,'pid':8,'name':'长野县','level':2},

];
// 北陆地区
citys[9] = [
    {'id':30,'pid':9,'name':'新泻县','level':2},
    {'id':31,'pid':9,'name':'石川县','level':2},
    {'id':32,'pid':9,'name':'福井县','level':2},
    {'id':33,'pid':9,'name':'富山县','level':2},
];

// 城市结构处理
var lineCitys = [].concat.apply([],citys).filter(function(val){
    return !(!val || val === "");
}),
    CitysCollect    =   [];

$.each( lineCitys, function(k,item){
    CitysCollect[item.id]   =   item;
} );

// 省份结构处理
var ProvinceCollect =   [];

$.each( provinces, function(k,item){
    ProvinceCollect[item.id]   =   item;
} );

/**
 * @ 地区数据
 * @returns {[null,null,null,null,null,null,null,null,null,null]}
 */
function fetchJapanProvincesData()
{
    return provinces;
}

/**
 * @ 根据地区ID获取城市数据
 * @param cityid
 * @returns {Array}
 */
function fetchJapanCityData( cityid )
{
    return (cityid == null) ? citys : citys[cityid];
}

/**
 * @ 是否是地区ID
 * @param nowid
 * @returns {boolean}
 */
function isProvinceByNowid(currentid)
{
    if( currentid == null || !provinces[currentid] )
        return false;
    return true;
}

/**
 * @ 根据城市ID 获取地区ID
 * @param pid
 * @returns {boolean}
 */
function getProvinceidByCityid(currentid)
{
    if( !currentid || isProvinceByNowid(currentid) )
        return false;
    var pid = false;
    $.each( lineCitys, function(k,item){
        if( item.id == currentid )
            pid = item.pid;
    } );
    return pid;
}
