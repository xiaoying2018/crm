var res = {};
res.uptoken = $("#token").val();
res.domain = "http://cdn.xiaoying.net";
var token = res.uptoken;
var domain = res.domain;
var config = {
  useCdnDomain: true,
  disableStatisticsReport: false,
  retryCount: 6,
  region: qiniu.region.z0
};
var putExtra = {
  fname: "",
  params: {},
  mimeType: null
};
uploadWithSDK(token, putExtra, config, domain);
imageControl(domain);
