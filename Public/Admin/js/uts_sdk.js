//uts sdk

//全局对象
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
            return [fileOperation.getFileType(f), f.size, +f.lastModifiedDate || f.name].join('_');
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

//封装的ajax请求 
(function () {
    function Glib() {
        this.JsonToParam = function (json) {
            var s = { a: 1, b: 2 }
            var arr = [];
            for (var p in json) {
                arr.push(p + "=" + json[p]);
            }
            return arr.join('&');
        }
    }

    function Ajax() {
        this.GXHR = null;
        this.CALLBACK = function () { };
        Glib.apply(this)
    }
    Ajax.prototype = {
        reviveXHR: function () {
            if (this.GXHR) { return }
            var _XHR,
        _msieXHR = [
        'Msxml2.XMLHTTP.5.0',
        'Msxml2.XMLHTTP.4.0',
        'Msxml2.XMLHTTP.3.0',
        'Msxml2.XMLHTTP',
        'Microsoft.XMLHTTP'
        ];
            for (var i = 0, l = _msieXHR.length; i < l; i++) {
                try {
                    if (_XHR = new ActiveXObject(_msieXHR[i])) break;
                } catch (e) {
                    _XHR = null;
                }
            }

            if (_XHR || (_XHR = new XMLHttpRequest, typeof XMLHttpRequest != 'undefined')) {
                return _XHR;
            }
            if (!_XHR) throw new Error("connection object not define.");
        },
        scope: function (fn, scope) {
            return function () { return fn.apply(scope) }
        },
        fmtFn: function (fn) {
            if (typeof fn == 'function') {
                this.CALLBACK = fn;
            }
            var _callback = this.CALLBACK || new Function;
            return _callback;
        },

        stateHandle: function () {
            var _self = this.GXHR;
            if (_self.readyState == 2) {
            } else if (_self.readyState == 4) {
                if (_self.status == 200) {
                    this.CALLBACK(_self.responseText);
                }
            }

        },
        fmtParam: function (data) {
            var urlParam = null;
            if (typeof data != 'string' && typeof data == 'object') {
                urlParam = this.JsonToParam(data);
            }
            return urlParam || data;
        },
        request: function (url, data, callback, method) {
            method = method || "GET";
            if (method == 'GET') {
                if (url.indexOf('?') != -1)
                    url += "&";
                else
                    url += "?";
                url += this.fmtParam(data);
            }
            var _self = this;
            _self.CALLBACK = this.fmtFn(callback);

            _self.GXHR = _self.GXHR || _self.reviveXHR();
            _self.GXHR.onreadystatechange = _self.scope(_self.stateHandle, _self);
            _self.GXHR.open(method, url, true);
            _self.GXHR.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            _self.GXHR.send(_self.fmtParam(data) || null);
        },

        get: function (url, data, callback) {
            return this.request(url, data, callback, "GET");
        },
        post: function (url, data, callback) {
            var url = url + '?uc1=5&uc2=8';
            return this.request(url, data, callback, 'POST');

        },
        getJSON: function (url, data, fn) {
            var oldScript = document.getElementById(url);
            if (oldScript) {
                oldScript.setAttribute("src", url);
                return;
            }
            var head = document.getElementsByTagName('head')[0];
            var script = document.createElement("script");
            script.setAttribute('type', 'text/javascript');
            script.setAttribute('src', url);
            script.setAttribute('id', url);
            head.appendChild(script);
            window['callback'] = function (data) {
                try {
                    fn && fn(data);
                } catch (e) { }
            };
            script.onload = script.onreadystatechange = function () {
                if ((!this.readyState || this.readyState === "complete" || this.readyState === "loaded")) {

                    script.onload = script.onreadystatechange = null; //IE内存溢出
                    if (head && script.parentNode) {
                        head.removeChild(script);
                    }
                }
            };
        }
    }
    window.simpleA = Ajax;
    window.Ajax = new Ajax;
})();

//记录cookie,存储token方便续传
(function () {
    window.html5UploadCookie = {
        setItem: function (key, value, expiresDays) {
            var date = new Date();
            date.setTime(date.getTime() + expiresDays * 24 * 3600 * 1000);
            document.cookie = key+"="+value+"; expires=" + date.toGMTString();
        },
        getItem: function (key) {
            var strCookie = document.cookie;
            var arrCookie = strCookie.split("; ");
            for (var i = 0; i < arrCookie.length; i++) {
                var arr = arrCookie[i].split("=");
                if (arr[0] == key) {
                    return arr[1];
                }
                if (i == arrCookie.length - 1) {
                    return false;
                }
            }
        },
        removeItem: function (key) {
            window.html5UploadCookie.setItem(key,"",-1);
        }
    };
})();

//回调函数
(function () {
    window.xhrEventCallback = function (uploadOptions) {
        this.loadstart = function (e, file, _self) {
            var currStack = _self.sliceStack;
            //currStack.inittime = (new Date()).getTime();  //标记开始 即时的 时间
            var transferedsize = _self.sliceStack ? _self.sliceStack.transferedsize : 0;
            currStack.transferedsize = transferedsize;
        };
        this.load = function (e, file, _self) {
            var res = _self.evalTo(e.target.responseText);
            if (!!res.transferedsize) {
                //记录断点续传token
                var tokenUrl = _self.uploadUrl.split('token').pop();
                var videoToken = tokenUrl.substr(1, tokenUrl.length - 1).split('&')[0];
                window.html5UploadCookie.setItem(window.fileOperation.getFileKey(file), videoToken, 30);
                
                var transferedsize = parseInt(res.transferedsize);
                _self.sliceStack.transferedsize = transferedsize;
                //transferedsize < file.size && _self.streamUpload(_self.currFile, _self.uploadUrl, transferedsize, _self);
            } else {
                if (!res.totalsize && res.status != '200') {
                    if (uploadOptions.error) {
                        uploadOptions.error(file, "上传失败");
                    }
                    window.html5UploadCookie.removeItem(window.fileOperation.getFileKey(file));
                    _self.xhrAbort();
                }
            }
            _self.sliceStack.starttime = (new Date()).getTime();
            if (res.transferedsize && res.transferedsize == res.totalsize) {
                if (uploadOptions.load) {
                    window.html5UploadCookie.removeItem(window.fileOperation.getFileKey(file));
                    uploadOptions.load(file);
                }
            }
        };
        this.progress = function (e, file, _self) {

            var sliceStack = _self.sliceStack, filetransfered = parseInt(_self.sliceStack.transferedsize);
            var pc = parseInt((filetransfered + e.loaded) / file.size * 100),
                delttime = ((new Date()).getTime() - (sliceStack.starttime || sliceStack.inittime)) / 1000,
                rate = e.loaded / delttime;
            rate = rate / 1024;
            rate = rate > 1024 ? (((rate / 1024 * 10) >> 0) / 10).toFixed(1) + "M/s" : (((rate * 10) >> 0) / 10).toFixed(1) + "K/s";

            //在上传过程中如果用户在电脑上删除了视频，会触发视频丢失错误
            if (isNaN(pc)) {
                uploadOptions.progress({ progress: "视频丢失", file: file, currXhr: _self });
                _self.xhrAbort();
            } else if (uploadOptions.progress) {
                uploadOptions.progress({ progress: pc + "%", speed: rate, file: file });
            }
        };
        this.error = function (e, file, _self) {
            if (uploadOptions.error) {
                uploadOptions.error(file);
            }
            _self.xhrAbort();
        };
        this.abort = function (e, file, _self) {
            if (uploadOptions.abort) {
                uploadOptions.abort(e);
            }
        };
    };
})();

//slice upload
(function () {
    window.sliceUpload = function () {
        this.sliceSize = 10485760;  //10M
        //this.sliceStack = {};
        this.xhr = null;
        this.sparent = null;

        this.streamUpload = function (url, file, sliceIndex, _parent) {

            if (!_parent)
                return;

            this.sparent = _parent;
            
            this.sparent.sliceArray[sliceIndex] -= 1;
            //var _self = this;
            this.xhr = new XMLHttpRequest();
            this.xhr.upload.addEventListener("progress", function (e) { _parent.progress(e, file, sliceIndex, this.sparent) }, false);
            this.xhr.addEventListener("loadstart", function (e) { _parent.loadstart(e, file, sliceIndex, this.sparent) }, false);
            this.xhr.addEventListener("error", function (e) { _parent.error(e, file, sliceIndex, this.sparent) }, false);
            this.xhr.addEventListener("abort", function (e) { _parent.abort(e, file, sliceIndex, this.sparent) }, false);
            this.xhr.addEventListener("load", function (e) { _parent.load(e, file, sliceIndex, this.sparent) }, false);

            var start = sliceIndex * this.sliceSize;
            var sliceFile = this.fileSlice(file, start);
            var content = start == -1 ? "bytes *" : "bytes " + (start + 1) + "-" + (start + sliceFile.size) + "/" + file.size;
            
            var newUrl = url.substr(0, url.length - 10);
            this.xhr.open("POST", newUrl, true)
            this.xhr.setRequestHeader("X_FILENAME", encodeURI(file.name));
            this.xhr.setRequestHeader("Content-Range", content);

            this.xhr.send(sliceFile);
        };

        this.fileSlice = function (file, start) {
          var blob;
          start = start || 0;
          var range = Math.min(file.size, start + this.sliceSize);
          if (start != -1) {
              if (file.slice) {
                  blob = file.slice(start, range);
              } else if (file.webkitSlice) {
                  blob = file.webkitSlice(start, range);
              } else if (file.mozSlice) {
                  blob = file.mozSlice(start, range);
              } else {
                  blob = file;
              }
          } else {
              return null;
          }
          return blob;
        };

        this.xhrAbort = function () {
            this.xhr && this.xhr.abort();
        }
    };
})();

//uts upload
(function () {
    window.UTSUpload = function () {
        this.concurrentCount = 2;
        this.sliceCount = 0;  //slice count of file
        this.sliceArray = []; //slice status : -n -> failed count, 0 -> waiting upload, 1 -> upload success
        this.sliceProgress = []; //slice uploaded size already
        this.sliceSize = 10485760;  //10M
        this.sliceStack = {};
        this.xhr = null;
        this.currFile = {};
        this.uploadUrl = "";
        this.currXhrEventCallback = {};
        this.sliceUploads = [];


        this.format = function () {
            var b, a = arguments,
                d = a[0] || {},
                f = 1,
                e = a.length;
            if (!e) return null;
            for (e === f && (d = this, --f) ; f < e; f++)
                if (null != (b = a[f]))
                    for (var c in b) d[c] = b[c];
            return d
        };
        this.evalTo = function (string) {
            try {
                return (new Function("return " + string))();
            } catch (e) {
                console && console.log("Error: " + string);
            }
        };

        this.initUpload = function(url, file, option, simpleAjax) {

            var data = { videoname: encodeURIComponent(file.name) }
            var _simpleAjax = simpleAjax;
            var argument = this.format(data, {
                client: "html5",
                uploadtype: 1, //slice upload
                file_size: file.size,
                t: (new Date).getTime(),
                uc1: option.uc1,
                uc2: option.uc2
            })
            var _self = this;


            if (window.html5UploadCookie.getItem(window.fileOperation.getFileKey(file))) {
                argument = { token: window.html5UploadCookie.getItem(window.fileOperation.getFileKey(file)), uploadtype: 1 };
            }

            simpleAjax.get(url, argument, function (reqData) {
                try {
                    var data = eval("(" + reqData + ")");
                } catch (e) {
                    var data = { code: 999 };
                }
                if (data.code == 0) {
                    var nextBlob = data.data.upload_size || 0;
                    var x = new window.xhrEventCallback(option);
                    _self.tryUpload(data.data.upload_url, file, x)

                } else {
                    switch (data.code) {
                        case 101: //登录超时;
                            alert("登录超时！")
                            window.location.reload();
                            break;
                        case 112:
                            var tempBool = confirm("code:[" + data.code + "]" + fileName + " 文件， 续传服务发生异常,您是否要重新上传？");
                            if(tempBool) {
                              setTimeout(function () {
                                    _self.initUpload(url, file, option, firstClassID, secondClassID, _simpleAjax);
                                }, 10000)
                            } else {
                              if(option.load) {
                                option.load(file,"暂时放弃此文件上传");
                              }
                            }
                            
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
                }
            });
            //this.tryUpload(uploadUrl, file option);
        };

        this.tryUpload = function (url, file, xhrEventCallback) {

            this.currFile = file;
            this.currXhrEventCallback = xhrEventCallback;
            this.uploadUrl = url;
            this.sliceCount = Math.ceil(file.size / this.sliceSize);  //slice count
            for (var index=0; index < this.sliceCount; index++) {
                this.sliceArray[index] = 0;
                this.sliceProgress[index] = 0;
            }
            this.sliceStack.transferedsize = 0;
            this.sliceStack.inittime = 0;
          
            var runCount = Math.min(this.concurrentCount, this.sliceCount - 1);
            runCount = Math.max(runCount, 1);
            for (var index = 0; index < runCount; index++) {

              var sliceUp = new window.sliceUpload();
              this.sliceUploads.push(sliceUp);

              sliceUp.streamUpload(url, file, index, this);
            }
        };

        this.load = function (e, file, sliceIndex, _self) {

          //upload failed , server return stauts != 200
          var res = this.evalTo(e.target.responseText);
          if (!res.totalsize && res.status != '200') {
            this.currXhrEventCallback.load(e, file, this);
            return;
          }

          this.sliceArray[sliceIndex] = 1;

          //file send complete
          if (sliceIndex == this.sliceCount - 1) {
            this.currXhrEventCallback.load(e, file, this);
            return;
          }

          //check no upload slice
          
          for (var index = sliceIndex + 1; index < this.sliceCount - 1; index++) {

            if (this.sliceArray[index] == 0) {
                var sliceUp = new window.sliceUpload();
                this.sliceUploads.push(sliceUp);
                sliceUp.streamUpload(this.uploadUrl, file, index, this);
                return;
            }
          }

          var flag = true;
          for (var index = 0; index < this.sliceCount - 1; index++) {
            if (flag && this.sliceArray[index] < 1) {
              flag = false;
              break;
            }
          }

          //send last slice
          if (flag) {
                var sliceUp = new window.sliceUpload();
                this.sliceUploads.push(sliceUp);
                sliceUp.streamUpload(this.uploadUrl, file, this.sliceCount - 1, this);
            }
        };

        this.loadstart = function (e, file, sliceIndex, _self) {
          if (this.sliceStack.inittime < 1) {
                this.sliceStack.inittime = (new Date()).getTime(); 
            }
          this.currXhrEventCallback.loadstart(e, file, this);
        };

        this.progress = function (e, file, sliceIndex, _self) {
          //
          this.sliceProgress[sliceIndex] = e.loaded;

          var transferedsize = 0;
          for (var index = 0; index < this.sliceProgress.length; index++) {
            transferedsize += this.sliceProgress[index];
          }

          if (transferedsize < this.sliceStack.transferedsize)
            return;

          this.sliceStack.transferedsize = transferedsize - e.loaded;
          this.currXhrEventCallback.progress(e, file, this);
        };

        this.error = function (e, file, sliceIndex, _self) {
          if (this.sliceArray[sliceIndex] <= -3) {
            this.currXhrEventCallback.error(e, file, this);
          } else {
            //this.sliceUploads[sliceIndex].streamUpload(this.uploadUrl, file, sliceIndex, this);
            var sliceUp = new window.sliceUpload();
            this.sliceUploads.push(sliceUp);
            sliceUp.streamUpload(this.uploadUrl, file, sliceIndex, this);
          }
        };

        this.abort = function (e, file, sliceIndex, _self) {
          this.currXhrEventCallback.abort(e, file, this);
        };

        this.xhrAbort = function () {
            for (var index = 0; index < this.sliceUploads.length; index++) {
                if (this.sliceUploads[index]) {
                    this.sliceUploads[index].xhrAbort();
                }
            }
        };
    };
})();


//start upload
(function () {

    //callback array
    window.callbacks = [];
    //file upload array
    window.fileuploads = [];
    //upload file fucntion
    window.uploadFunction = function (obj) {
        var cb = new Object();
        cb.uploadUrl = obj.uploadUrl;
        cb.file = obj.file;
        cb.uc1 = obj.uc1;
        cb.uc2 = obj.uc2;
        cb.load = obj.load;
        cb.progress = obj.progress;
        cb.error = obj.error;
        cb.abort = obj.abort;
        window.callbacks.push(cb);

        var fu = new window.UTSUpload();
        window.fileuploads.push(fu);
        var simpleAjax = new window.simpleA();

        fu.initUpload(cb.uploadUrl, cb.file, cb, simpleAjax);
    }

    window.abortFunction = function (filename) {
        for (var i = fileuploads.length - 1; i >= 0; i--) {
            if (fileuploads[i].currFile.name == filename) {
                fileuploads[i].xhrAbort();
            }
        };
    }

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

