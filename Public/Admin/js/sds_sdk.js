//start upload



$(function () {
    $("#fileSelecter").selectUpload({
        selectFileId: "fileSelecter", //绑定添加文件按钮
        addFiles: function (file, fileType) {//每添加一个视频文件的回调
            $("#videoName").html(file.name);
            $("#videoId").html(file.name);
            $("#videoProgress").html("0");
            $("#videoSpeed").html("0");
            $("#videoStatus").html("等待上传");
        }
    });





});

////////////////////////////////////////////////////////////////////////
// upload file
////////////////////////////////////////////////////////////////////////
(function () {
    window.uploadFunction = function (obj) {

        var param = new window.uploadParams();
        param.uploadUrl = obj.uploadUrl;
        param.file = obj.file;
        param.uploadType = 0;  // 0(whole), 1(slice)
        param.fileKey = window.fileOperation.getFileKey(obj.file);
        param.testSpeed = 1;
        param.nodeid = $("input[name='nodeuploads']:checked").val();
        if (param.nodeid == undefined)
            param.nodeid = 0;

        window.LCUploader(param);
    }

    window.abortFunction = function (fileKey) {
        window.SDSAbort(fileKey);
    }

})();


//回调函数
(function () {
    window.uploadParams = function () {
        this.uploadUrl = "";
        this.file = {};
        this.uc1 = "0";
        this.uc2 = "0";

        this.init = function (data) { //一个文件上传完成后的回调
          switch (data.code) {
              case 101: //登录超时;
                  alert("登录超时！")
                  window.location.reload();
                  break;
              case 112:
                  alert("续传服务发生异常,请重新上传！");
                  break;
              case 305:
                  alert("code:[" + data.code + "] " + "您的上传空间不足，如需更多空间，请联系客服人员。");
                  break;
              case 999:
                  alert("code:[999] " + "初始化请求错误，请联系后台。");
                  break;
              default:
                  alert("code:[" + data.code + "] " + data.message);
          }
        };

        this.load = function (code, e) { //一个文件上传完成后的回调
            if (code == CallbackState.giveup) {
                $("#videoSpeed").html("0");
                $("#videoStatus").html("暂时放弃此文件上传");
            } else {
                $("#videoProgress").html("100%");
                $("#videoSpeed").html("0");
                if (e.download_url) {
                    $("#videoStatus").html(e.download_url);
                } else {
                    $("#videoStatus").html("上传完成");
                }
            }
        };

        this.progress = function (code, e) {//上传进度回调
            if (code == CallbackState.lost) {
                $("#videoSpeed").html(0);
                $("#videoStatus").html("视频丢失");
            } else {
                $("#videoId").html(e.videoId);
                $("#videoProgress").html(e.progress);
                $("#videoSpeed").html(e.speedDisplay);
                $("#videoStatus").html("正在上传");
            }
        };

        this.error = function (code, e) {//发上错误回调
            if (code == CallbackState.failed) {
                $("#videoSpeed").html("0");
                $("#videoStatus").html("上传失败");
            } else {
                $("#videoSpeed").html("0");
                $("#videoStatus").html("网络连接断开");
            }
        };

        this.abort = function (e) { //终止上传

        };
    };
})();


////////////////////////////////////////////////////////////////////////
// test upload speed
////////////////////////////////////////////////////////////////////////
(function () {
    window.testFunction = function (obj) {

        var param = new window.uploadTestParams();
        param.uploadTestUrl = obj.uploadTestUrl;

        var st = new window.SpeedTest(param);
        st.testNode();
    }

})();


//回调函数
(function () {
    window.uploadTestParams = function () {
        this.uploadTestUrl = "";
        this.fileLength = 1 * 1024 * 1024;

        $("#nodeGroup").empty();

        this.init = function (data) { //一个文件上传完成后的回调
          switch (data.code) {
              case 101: //登录超时;
                  alert("登录超时！")
                  window.location.reload();
                  break;
              case 112:
                  alert("续传服务发生异常,请重新上传！");
                  break;
              case 305:
                  alert("code:[" + data.code + "] " + "您的上传空间不足，如需更多空间，请联系客服人员。");
                  break;
              case 999:
                  alert("code:[999] " + "初始化请求错误，请联系后台。");
                  break;
              default:
                  alert("code:[" + data.code + "] " + data.message);
          }
        };

        this.load = function (code, e) { //一个文件上传完成后的回调
            if (code == CallbackState.giveup) {
                $("#videoSpeed").html("0");
                $("#videoStatus").html("暂时放弃此文件上传");
            } else {

                var radio = '<input type="radio" name="nodeuploads" id="nodeId_' + e.nodeId + '" value="' + e.nodeId + '" /><label for="nodeId_' + e.nodeId + '">' + e.nodeName + ' ' + e.speed + 'K/s' + '</label>';
                $("#nodeGroup").append($(radio));
                $("#nodeGroup").append('&nbsp;&nbsp;');

                $("#videoStatus").html("测速完成");
            }
        };

        this.progress = function (code, e) {//上传进度回调
            if (code == CallbackState.lost) {
                $("#videoSpeed").html(0);
                $("#videoStatus").html("视频丢失");
            } else {
                $("#videoId").html(e.nodeName);
                $("#videoProgress").html(e.progress);
                $("#videoSpeed").html(e.speedDisplay);
                $("#videoStatus").html("正在上传");
            }
        };

        this.error = function (code, e) {//发上错误回调
            if (code == CallbackState.failed) {
                $("#videoSpeed").html("0");
                $("#videoStatus").html("上传失败");
            } else {
                $("#videoSpeed").html("0");
                $("#videoStatus").html("网络连接断开");
            }
        };

        this.abort = function (e) { //终止上传

        };
    };
})();


(function () {
    window.fileOperation = {
        selectFile: [],
        maxFileLength: 100,
        fileKeyList: {},
        fileTypes: "wmv|avi|dat|asf|rm|rmvb|ram|mpg|mpeg|3gp|mov|mp4|m4v|dvix|dv|dat|mkv|flv|f4v|vob|ram|qt|divx|cpk|fli|flc|mod",
        getFileType: function (f) {
            return f.name.split('.').pop();
        },
        getFileKey: function (f) {
            return [fileOperation.getFileType(f), f.size, + f.lastModifiedDate || f.name].join('_');
        },
        fileSelect: function (e) {
            var inpfile = document.getElementById("fileUploadId");
            if (inpfile) {
                inpfile.click && e.target != inpfile && inpfile.click();
            } else {
                inpfile = document.createElement('input');
                $("body").append(inpfile);
                inpfile.setAttribute('id', "fileUploadId");
                inpfile.setAttribute('type', "file");
                inpfile.setAttribute('autocomplete', "off");
                inpfile.setAttribute('multiple', "true");
                inpfile.style.display = "none";
                inpfile.addEventListener('change', fileOperation.showFileList, !1);
                inpfile.click && e.target != inpfile && inpfile.click();
            }
        },
        showFileList: function (e) {
            var files = e.target.files || e.dataTransfer.files;
            var m = fileOperation.maxFileLength - fileOperation.selectFile.length;
            for (var i = 0; i < Math.min(files.length, m) ; i++) {
                var f = files[i],
                    fType = fileOperation.getFileType(f),
                    fKey = fileOperation.getFileKey(f);

                if (eval("/" + window.fileOperation.fileTypes + "$/i").test(fType) == false) {
                    if (files.length == 1) {
                        alert("不支持该视频格式！");
                    } else {
                        alert("包含不支持的视频格式");
                    }
                }
                if (eval("/" + fileOperation.fileTypes + "$/i").test(fType) && fileOperation.selectFile.length < window.fileOperation.maxFileLength) {
                    if (!fileOperation.fileKeyList[fKey]) {
                        fileOperation.selectFile.push(f);
                        window.userSelectedFiles = [];//针对浙江在线的需求
                        window.userSelectedFiles.push(f);//供访问的文件列表
                        fileOperation.fileKeyList[fKey] = 1;


                        //添加文件回调函数
                        window.selectOptions.addFiles(f, fType);
                    }
                }
            }
        }
    };
})();


//绑定选择文件按钮
(function ($) {
    var selectOptions = {};
    var defaults = {
        selectFileId: "selectFile",
        addFiles: function (file, fileType) {

        },
    };
    $.fn.selectUpload = function (options) {
        selectOptions = $.extend(defaults, options || {});
        return this.each(function () {
            $("#" + selectOptions.selectFileId).click(window.fileOperation.fileSelect);
            window.selectOptions = selectOptions;
        });
    };
})(jQuery);