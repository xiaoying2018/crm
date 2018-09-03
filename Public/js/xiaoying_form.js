(function($) {
    
/**
 * @ 构造方法
 * @param fields
 * @param denger
 * @returns {Validator}
 * @constructor
 */
var Validator = function (fields,denger) {
    var that            =   this;
    // 验证配置
    that.Config =   {
        Verify: {
            required:function (node) {
                return that.nodeValue(node).length > 0
                    ? true
                    : that.setErrorMessage(node,'不能为空');
            }
            ,phone:function (node) {
                var value       =   that.nodeValue(node);
                // 为空不验证
                if( value == '' )   return true;
                return that.Config._Regular.phone.test(value)
                    ? true
                    : that.setErrorMessage(node,'不合法');
            }
            ,max:function (node,param) {
                var value       =   that.nodeValue(node);
                // 为空不验证
                if( value == '' )   return true;
                return value.length <= param
                    ? true
                    : that.setErrorMessage(node,'超出最大限制'+param);
            }
            ,min:function (node,param) {
                var value       =   that.nodeValue(node);
                // 为空不验证
                if( value == '' )   return true;

                return value.length >= param
                    ? true
                    : that.setErrorMessage(node,'小于最小限制'+param);
            }
            ,in:function (node,param) {
                var allow       =   param.split(','),
                    value       =   that.nodeValue(node),
                    result      =   false;
                // 为空不验证
                if( value == '' )   return true;
                allow.forEach(function(item){
                    if( item == value ){
                        result =    true;
                        return;
                    }
                });
                return result
                    ? true
                    : that.setErrorMessage(node,value+"不在"+param+'范围之内');
            }
            ,multi:function (node,param) {
                var allow       =   param.split(','),
                    value       =   that.nodeValue(node),   //array
                    result      =   true,
                    trigger     =   ''    ;
                // 为空不验证
                if( value == '' || value == 'undefined' ){
                    return true;
                }
                value.forEach( function (v) {
                    if( allow.indexOf(v) == -1 ){
                        trigger = v;
                        result = false; return ;
                    }
                } );

                return result
                    ? true
                    : that.setErrorMessage(node,trigger+"不在"+param+'范围之内');
            }
            ,number:function (node,param) {
                var value       =   that.nodeValue(node);
                // 为空不验证
                if( value == '' )   return true;
                return /^[0-9]+$/.test(value)
                    ?   true
                    :   that.setErrorMessage(node,'不是数字');
            }
            ,email:function (node) {
                var value       =   that.nodeValue(node);
                // 为空不验证
                if( value == '' )   return true;

                return that.Config._Regular['email'].test(value)
                    ?   true
                    :   that.setErrorMessage(node,'格式不对');
            }
        }
        // 正则
        ,_Regular:{
            phone: /^1[34578][0-9]{9}$/,
            url: /(^#)|(^http(s*):\/\/[^\s]+\.[^\s]+)/,
            number:/^[1-9][0-9]*$/,
            email:/^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/
        }
        // 字段中文映射
        ,_Map: {
            name        :   '姓名'
            ,tel        :   '电话'
            ,nl         :   '日语等级'
            ,year       :   '出国年份'
            ,come       :   '来校咨询'
            ,email      :   '邮箱'
        }
    };
    that.fields             =   fields;                     // 验证字段
    that.denger             =   denger ? true : false;      // 是否抛出异常
    that.errorMessage       =   '';                         // 内部错误信息
    that.errorNode          =   '';                         // 内部错误节点
    that.showError          =   '';                         // 外部错误信息
    that.rules              =   [];                         // 储存验证规则
    that.passed             =   {};                         // 验证后字段数据信息

    return that;
};

/**
 * @ 验证方法
 * @param config
 * @returns {*}
 * @constructor
 */
Validator.prototype.Verify = function (config) {
    var that                    =   this,
        resolvedRules           =   that.Resolve( config );     // 解析验证规则 return Array
    try{
        // 遍历字段 验证字段下定义规则
        Object.keys(resolvedRules).forEach(function (key) {
            var currentRules            =   resolvedRules[key];
            Object.keys(currentRules).forEach( function (k,i) {
                // 当前字段下 子验证规则
                var subRule         =   currentRules[k],
                    subType         =   subRule['type'],
                    subParam        =   subRule.length == 1 ? '' : subRule['param'];

                that.Config.Verify[subType](key,subParam) || that.Exception(that.errorMessage,key);
            } );
            that.passed[key]        =   that.nodeValue(key);
        });

        return that.passed;
    }catch (E){
        // console.log(E);
        that.showError = E.message;
        return false;
    }
};

/**
 * @ 参数处理
 * @param config
 * @constructor
 */
Validator.prototype.Resolve = function (config) {
    var that            =   this;
    Object.keys(config).forEach( function (key,index) {
        // 分割当前字段的验证规则
        var basis           =   config[key].split("|"),
            current         =   [];
        // 遍历处理子规则
        basis.forEach( function (k, v) {
            var spilt       =   k.split(':'),
                item        =   [];
            // current.isPass  =   false;           // 忽略字段验证
            item['type']            =   spilt[0];
            item['param']           =   spilt.length == 1 ? '' : spilt[1] ;
            current.push( item );
        } );
        that.rules[key]     =   current;
    } );

    return that.rules;
};

/**
 * @ 获取节点值
 * @param node
 * @returns {*}
 */
Validator.prototype.nodeValue = function (node) {
    var that            =   this;
    return that.fields.hasOwnProperty(node)
        ?   that.fields[node]
        :   that.nodeMultiValue(node);
};

/**
 * @ 获取多功能节点值
 * @param node
 */
Validator.prototype.nodeMultiValue = function (node) {
    var that            =   this,
        fields          =   that.fields,
        value           =   [],
        reg             =   eval("/^"+node+"\\[[0-9]+\\]$/");

    Object.keys(fields).forEach( function (alias) {
        if( reg.test(alias) )  value.push( fields[alias] );
    } );

    return value;
};

/**
 * @ 异常抛出
 * @param message
 * @param node  节点
 * @constructor
 */
Validator.prototype.Exception = function (message,node) {
    throw new Error(message);
};

/**
 * @ 设置内部错错误信息
 * @param message
 * @returns {boolean}
 */
Validator.prototype.setErrorMessage = function (node,message){
    var that        =   this,
        alias       =   that.Config._Map.hasOwnProperty(node)
            ? that.Config._Map[node]
            : node;
    that.errorNode      =   node;
    that.errorMessage   =   alias+message;
    return false;
}

var _arr = []
$.xiaoyingForm = function(el, options) {
    var formDv = $(el);
    formDv.vars = $.extend({}, $.xiaoyingForm.defaults, options);
    methods = {
        fingerprint: "",
        intervalId: "",
        _currentField: [],
        _mustField: [],
        _allField: [],
        _sendData: {},
        _api: "",
        _valid: [],
        _token: {},
        _verify:"",
        init: function() {
            var _this = this;
            this.filterData();
            $(document).on('click', formDv.vars.submitBtn, function() {
                // alert(123)
                _this.readySendData();
            });
            if (formDv.vars.sendMes) {
                $(document).on('click', formDv.vars.sendMes, function() {
                    _this.sendMessage();
                });
            }
        },

        filterData: function() {
            var _this = this;
            var res = window.res;
            _this._verify = res._verify;
            for (var key in res._must) {
                res._must[key].FLAG = key;
                _this._mustField.push(res._must[key])
            }
            //获取项目的字段
            var _c = formDv.vars.parameter;
            if (_c.length > 0) {
                for (var i = 0; i < _c.length; i++) {
                    for (var key in res._aliasMap) {
                        if (_c[i].id == key) {
                            var _t = {};
                            _t = res._aliasMap[key]
                            _t.FLAG = key;
                            _t.rename = _c[i].rename;
                            _t.placeholder = _c[i].placeholder;
                            _t.reg = _c[i].reg;
                            _this._currentField.push(_t)
                        }
                    }
                }
                // _this._allField = _this._currentField.concat(_this._mustField);

                //去重
                for (var b = 0; b < _this._currentField.length; b++) {
                    for (var c = 0; c < _this._mustField.length; c++) {
                        if (_this._currentField[b].FLAG != _this._mustField[c].FLAG) {
                            _this._allField.push(_this._currentField[b]);
                            break;
                        }
                    }
                }

                if (formDv.vars.beforeLoadField) {
                    _this._allField = formDv.vars.beforeLoadField(_this._allField);
                }
                var _json = {};
                $.each(_this._allField, function(k, v) {
                    if (v.reg && v._v.indexOf(v.reg) < 0) {
                        _json[v.FLAG] = v.reg + "|" + v._v;
                    } else {
                        _json[v.FLAG] = v._v;
                    }
                });

                //处理验证规则
                _this._valid = _json;
                _this.setBody();
                _this._api = res._api;
                _this._token = res._token;
            }
        },
        /**
         * 渲染HTML结构
         */
        setBody: function() {
            var _this = this;
            var _html = "";
            for (var i = 0; i < _this._allField.length; i++) {
                var _c = _this._allField[i],
                    _ele,
                    _type;

                if (_c.type == 1) {
                    _type = "INPUT";
                } else if (_c.type == 2) {
                    _type = "SELECT";
                } else if (_c.type == 3) {
                    _type = "RADIO";
                } else if (_c.type == 4) {
                    _type = "CHECKBOX";
                } else if (_c.type == 5) {
                    _type = "TEXTAREA";
                } else {
                    setErrorMessage('CRM暂无该类型数据,请联系CRM管理员');
                }
                if ($(formDv).find("[data-templet='CRM_TEMPLET_" + _c.FLAG + "']").length > 0) {
                    _ele = $(formDv).find("[data-templet='CRM_TEMPLET_" + _c.FLAG + "']").children();
                } else {
                    _ele = $(formDv).find("[data-templet='CRM_TEMPLET_" + _type + "']").children();
                }
                if (_ele.length < 1) {
                    _this.setErrorMessage("请定义" + "[data-templet='CRM_TEMPLET_" + _type + "']" + "模板");
                    return false;
                }

                var _name, _placeholder;
                _c.rename ? _name = _c.rename : _name = _c.name;

                _c.placeholder ? _placeholder = _c.placeholder : _placeholder = _name;
                //文本
                if (_type == "INPUT") {
                    $(_ele).find("input").attr("placeholder", _placeholder);
                    $(_ele).find(".CRM_TEMPLET_NAME").text(_name);
                    $(_ele).find("input").attr("name", _c.FLAG);
                    _html = _html + $(_ele).parent().html();
                }
                //下拉
                else if (_type == "SELECT") {
                    var _option = "";
                    $(_ele).find(".CRM_TEMPLET_NAME").text(_name);
                    $(_ele).find("select").attr("placeholder", _c.name);
                    $(_ele).find("select").attr("name", _c.FLAG);
                    _option += "<option value=''>" + _name + "</option>";
                    for (var key in _this._allField[i].value) {
                        if (!_this._allField[i].value[key].id) {
                            _option += "<option value='" + _this._allField[i].value[key] + "'>" + _this._allField[i].value[key] + "</option>"
                        } else {
                            _option += "<option value='" + _this._allField[i].value[key].id + "'>" + _this._allField[i].value[key].name + "</option>"
                        }
                    }
                    $(_ele).find("select").html(_option);
                    _html = _html + $(_ele).parent().html();
                }
                //单选
                else if (_type == "RADIO") {
                    for (var key in _this._allField[i].value) {
                        $(_ele).find(".CRM_TEMPLET_NAME").text(_this._allField[i].value[key]);
                        $(_ele).find("input").attr("name", _c.FLAG);
                        $(_ele).find("input").attr("title", _this._allField[i].value[key]);
                        $(_ele).find("input").attr("value", _this._allField[i].value[key]);
                        _html = _html + $(_ele).parent().html();
                    }
                }
                //多选
                else if (_type == "CHECKBOX") {
                    var _sele = $(_ele).find(".CRM_TEMPLET_OPTION");
                    var _t = "";
                    for (var key in _this._allField[i].value) {
                        $(_sele).find(".CRM_TEMPLET_NAME").text(_this._allField[i].value[key]);
                        $(_sele).find("input").attr("name", _c.FLAG);
                        $(_sele).find("input").attr("title", _this._allField[i].value[key]);
                        $(_sele).find("input").attr("value", _this._allField[i].value[key]);
                        _t = _t + $(_sele).html()
                        // console.log("_t",_t);
                    }
                    $(_sele).html(_t)
                    _html = _html + $(_ele).parent().html();
                }
            }
            $(formDv).html(_html);

            if (formDv.vars.afterLoadField) {
                formDv.vars.afterLoadField();
            }
        },
        /**
         * 验证字段
         * @return {[type]} [description]
         */
        readySendData: function() {
            if (!$(formDv.vars.submitBtn).hasClass("disabled")) {
                var _formJson = {};
                var _this = this;

                $.each(_this._allField, function(k, v) {
                    //多选
                    if (v.type == 4) {
                        _this._sendData[v.FLAG] = []
                        $("input[name='" + v.FLAG + "']:checked").each(function(c) {
                            _this._sendData[v.FLAG].push($(this).val());
                        });
                    } else {
                        _this._sendData[v.FLAG] = $(formDv).find("[name='" + v.FLAG + "']").val();
                    }


                	if (v.FLAG == "XY_b10" && v.type == 2) {
                		_this._sendData["XY_b10"] = [_this._sendData["XY_b10"]]
                	}
                	// console.log("xxx",v);
                });
                var V = new Validator(_this._sendData),
                    result = V.Verify(_this._valid);
                if (!result) {
                    var _mes;
                    if (formDv.vars.validatorError) {
                        var _c = formDv.vars.parameter;
                        for (var i = 0; i < _c.length; i++) {
                            if (_c[i].id == V.errorNode) {
                                if (_c[i].errorMes) {
                                    _mes = _c[i].errorMes;
                                } else {
                                    _mes = V.showError;
                                }
                            }
                        }
                        formDv.vars.validatorError(_mes, $(formDv).find("[name='" + V.errorNode + "']"))
                    } else {
                        alert("xxx", V.showError);
                    }
                } else {
                    _this._sendData[_this._token.sign] = _this._token.value;
                    _this._sendData["XY_b28"] = window.location.href;
                    _this._sendData["XY_c01"] = _this.getQueryString('goto');

                    if (window.location.href.indexOf('xiao-ying.net') > -1) {
                        _this._sendData["XY_c01"] = "sem";
                    }
                    formDv.vars.beforeSendData(_this._sendData, function() {
                        console.log("_sendData", _this._sendData);
                        _this.sendData();
                    })
                }
            }
        },
        /**
         * 传输数据
         * @return {[type]} [description]
         */
        sendData: function() {
            var _btn = formDv.find(formDv.vars.submitBtn);
            if (_btn.hasClass("disabled")) {
                return false;
            }
            var _this = this;
            _this.setElementDisabled(formDv.vars.submitBtn, '处理中...');
            realSend();

            function realSend() {
                jQuery.support.cors = true;
                $.ajax({
                    url: _this._api,
                    data: _this._sendData,
                    dataType: 'jsonp',
                    type: 'get',
                    success: function(result) {
                        // _this.setElementDisabled(formDv.vars.submitBtn, "已成功提交");
                        if (result.status) {
                            _this.setElementDisabled(".xy_submit", "已成功提交");
                            if (formDv.vars.submitSuccess) {
                                formDv.vars.submitSuccess();
                            } else {
                                alet('提交成功！');
                            }
                        }else{
                            alert(result.message)
                            _this.setElementAbled(formDv.vars.submitBtn, "立即提交");
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        formDv.vars.submitError();
                        _this.setElementAbled(formDv.vars.submitBtn, "立即提交");
                        // alert(result.message)
                        return false;
                    },
                    complete: function(XMLHttpRequest, textStatus) {
                        // console.log("complete", XMLHttpRequest, textStatus);
                    }
                });
            }
        },
        /**
         * 用户指纹 一台机器对应一个指纹
         * @return {[type]} [description]
         */
        getfingerprint: function() {
            var fingerprint = new Fingerprint().get() + "" + new Fingerprint({
                canvas: true
            }).get() + "" + new Fingerprint({
                screen_resolution: true
            }).get();
            return fingerprint;
        },
        /**
         * 发送短信验证码
         * @return {[type]} [description]
         */
        sendMessage: function() {
            console.log("old",this._token);
            var _this = this;
            var _btn = formDv.find(formDv.vars.sendMes);
            var _phonenum = $("[name='XY_a02']").val();
            if (_btn.hasClass("disabled")) {
                return false;
            }
            if (_phonenum.length == 0 || _phonenum.length != 11) {
                alert('手机格式不正确');
                return false;
            }
            this.setElementDisabled(_btn,'发送中');
            var _token = {};
            var _data = {
                XY_a02: _phonenum,
            };
            _data[_this._token.sign] = _this._token.value;
            console.log("xxx",_data);
            $.ajax({
                type: "post",
                url: _this._verify,
                data: _data,
                dataType: "jsonp",
                jsonp: "callback",
                jsonpCallback: "JsonCallback",
                success: function(result) {
                    if (result.status) {
                        _this._token = result._token;
                        _this.startTimeOut(_btn)
                        // alert('短信发送成功，请注意查收！')
                    } else {
                        _this.setElementAbled(_btn,'获取验证码');
                        alert(result.message);
                    }
                },
                error: function(err) {
                    // console.log(err + '发送短信验证失败');
                }
            });
        },
        setElementDisabled: function(_btn, _mes) {
            $(_btn).addClass("disabled").attr("disabled", "disabled");
            if ($(_btn).is('input')) {
                $(_btn).val(_mes);
            } else {
                $(_btn).text(_mes);
            }
        },
        setElementAbled: function(_btn, _mes) {
            $(_btn).removeClass("disabled").removeAttr("disabled");
            $(_btn).removeClass("disabled").removeAttr("disabled");
            if ($(_btn).is('input')) {
                $(_btn).val(_mes);
            } else {
                $(_btn).text(_mes);
            }
        },
        startTimeOut: function(_btn) {
            console.log("xxx",$(_btn).html());
            var _this = this;
            if (    $(_btn).hasClass("disabled")) {
                var num = 120
                // var num = formDv.vars.sendMes.timeOut ? formDv.vars.sendMes.timeOut : 100;
                _this.intervalId = window.setInterval(function() {
                    num = num - 1;
                    $(_btn).text(num + "s");
                    if (num == 0) {
                        _this.setElementAbled(_btn,'获取验证码');
                        window.clearInterval(_this.intervalId);
                    }
                }, 1000);
            }
        },
        /**
         * 获取浏览器对应参数
         * @param  {[type]} name       [description]
         * @param  {[type]} needdecoed [需要解码的参数]
         * @return {[type]}            [description]
         */
        getQueryString: function(name, needdecoed) {
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
            var lh = window.location.search;
            if (needdecoed) {
                lh = decodeURI(window.location.search)
            }
            var r = lh.substr(1).match(reg);
            if (r != null) return unescape(r[2]);
            return null;
        },
        /**
         * 内部异常
         * @param {[type]} node    [description]
         * @param {[type]} message [description]
         */
        setErrorMessage: function(errorMes) {
            console.error(errorMes);
            return false;
        }
    };
    methods.init();
};
$.xiaoyingForm.defaults = {
    "url": "http://crm.xiaoying.net/api/lead/fields",
    // url:"http://192.168.31.85:8009/api/lead/fields",
    "parameter": [],
    "submitBtn": "#submitBtn",
    "onlyOnce": false,
    "sendMes": false,
    submitSuccess: function() {},
    submitError: function() {}
}
$.fn.xiaoyingForm = function(options) {
    var _this = $(this);
    _arr.push({el:_this,option:options});
    if ($(".xy_form").length == _arr.length) {
        readField();
    }
}
function readField(){
    $.ajax({
        type: "GET",
        // url: "http://192.168.0.160:8087/api/lead/fields",
        url: "http://crm.xiaoying.net/api/lead/fields",
        dataType: "jsonp",
        crossDomain: true,
        success: function(res) {
            if (res) {
                window.res = res;
                for (var i = 0; i < _arr.length; i++) {
                    if (_arr[i] === undefined) {
                        _arr[i] = {};
                    }
                    if (typeof _arr[i] === "object") {
                        new $.xiaoyingForm(_arr[i].el,_arr[i].option);
                    }
                }

            }

        },
        error: function(error) {
            console.log(error);
        }
    });
}
})(jQuery);