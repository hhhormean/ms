/////////////////////////////////////////////////////////////////////////////////////////////////
//Gandalf : software define storage upload sdk
/////////////////////////////////////////////////////////////////////////////////////////////////

var SliceState = {"failed" : -1, "ready" : 0, "waiting" : 1, "uploading" : 2, "success" : 3};
var CallbackState = {"failed" : 800, "lost" : 801, "giveup" : 802, "success" : 0};

//start upload
(function () {

    //callback array
    window.callbacks = [];
    //file upload array
    window.producers = [];
    //upload file fucntion
    window.LCUploader = function (obj) {
        var cb = new Object();
        cb.uploadUrl = obj.uploadUrl;
        cb.file = obj.file;
        cb.uc1 = obj.uc1;
        cb.uc2 = obj.uc2;
        cb.fileKey = obj.fileKey;
        cb.uploadType = obj.uploadType;
        cb.nodeid = obj.nodeid;
        cb.init = obj.init;
        cb.load = obj.load;
        cb.progress = obj.progress;
        cb.error = obj.error;
        cb.abort = obj.abort;
        window.callbacks.push(cb);

        var pr = new window.Producer();
        window.producers.push(pr);
        var simpleAjax = new window.simpleA();

        pr.tryProduce(cb.uploadUrl, cb.file, cb, simpleAjax);

    }

    window.SDSAbort = function (fileKey) {
        for (var i = producers.length - 1; i >= 0; i--) {
            if (producers[i].uploadOption.fileKey == fileKey) {
                producers[i].xhrAbort();
            }
        };
    }

})();


//test upload
(function () {
    window.SpeedTest = function (obj) {

        this.testParam = {};

        this.testParam.uploadTestUrl = obj.uploadTestUrl;
        this.testParam.fileLength = obj.fileLength;
        this.testParam.init = obj.init;
        this.testParam.load = obj.load;
        this.testParam.progress = obj.progress;
        this.testParam.error = obj.error;
        this.testParam.abort = obj.abort;

        this.urlSuffix = '';
        this.nodePos = 0;
        this.uploadPos = 0;
        this.hostsTest = [];
        this.nodesTest = [];
        this.nodesSpeed = [];
        this.nodeNames = [];

        this.testProducer;

        this.params = {};

        this.testNode = function() {

            var simpleAjax = new window.simpleA();
            var _self = this;

            //_self.param = new Object();
            _self.params.uploadUrl = '';
            _self.params.uploadType = 0;  // 0(whole), 1(slice)
            _self.params.fileKey = '';
            _self.params.file = {};
            _self.params.uc1 = "0";
            _self.params.uc2 = "0";

            _self.params.load = this.load;
            _self.params.progress = this.progress;
            _self.params.error = this.error;
            _self.params.abort = this.abort;

            _self.params.pparent = _self;

            simpleAjax.get(_self.testParam.uploadTestUrl, '', function (reqData) {
                try {
                    var data = eval("(" + reqData + ")");
                } catch (e) {
                    var data = { code: 999 };
                }

                if (data.code == 0) {
                    var testUrl = data.testUrl;
                    var hosts = eval(data.hosts);
                    var nodes = eval(data.nodes);
                    var names = eval(data.names);

                    _self.urlSuffix = testUrl;
                    _self.hostsTest = hosts; //hosts.split(',');
                    _self.nodesTest = nodes; //nodes.split(',');
                    _self.nodeNames = names; //names.split(',');
                    _self.nodePos = 0;

                    var url = 'http://' + _self.hostsTest[_self.nodePos] + _self.urlSuffix;
                    _self.params.uploadUrl = url;
                    _self.params.file = _self.testFile(_self.testParam.fileLength);

                    _self.testProducer = new window.Producer();
                    _self.testProducer.tryUpload(url, _self.params.file, _self.uploadPos, _self.params);

                } else {

                    _self.testParam.init(data);
                }
            });
        };

        this.testFile = function (len) {

        　　len = len || 32;
        　　var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        　　var maxPos = chars.length;
        　　var str = '';
        　　for (i = 0; i < len; i++) {
                str += chars.charAt(Math.floor(Math.random() * maxPos));
        　　}
        　　
            var blob = new Blob([str],{type : 'text/plain'});

            return blob;
        };

        this.load = function (code, e, _self) { //一个文件上传完成后的回调

            if (code == CallbackState.giveup) {

                this.xhrAbort();
                return;
            } else {

                e.nodeName = _self.nodeNames[_self.nodePos];
                e.nodeId = _self.nodesTest[_self.nodePos];
                e.speed = _self.nodesSpeed[_self.nodePos];

                _self.testParam.load(code, e);

                _self.nodePos += 1;
                if (_self.nodePos >= _self.hostsTest.length)
                    return;

                var url = 'http://' + _self.hostsTest[_self.nodePos] + _self.urlSuffix;
                _self.params.uploadUrl = url;
                _self.params.file = _self.testFile(_self.testParam.fileLength);
                _self.testProducer = new window.Producer();
                _self.testProducer.tryUpload(_self.params.uploadUrl, _self.params.file, _self.uploadPos, _self.params);
            }

        };

        this.progress = function (code, e, _self) {//上传进度回调

            if (code == CallbackState.lost) {

            } else {

                _self.nodesSpeed[_self.nodePos] = e.speed;

                e.nodeName = _self.nodeNames[_self.nodePos];
                e.speedDisplay = '测速: ' + e.speedDisplay;
                _self.testParam.progress(code, e);
            }
        };

        this.error = function (code, e, _self) {//发上错误回调

            if (code == CallbackState.failed) {

            } else {

            }
        };

        this.abort = function (e) { //终止上传

        };

    };
})();


//file upload
(function () {
    window.Consumer = function () {

        this.xhr = null;
        this._parent = null;


        this.getBoundary = function() {
            return "LECLOUD---------------------" + (new Date).getTime();
        }

        this.buildData = function(fileInfo, boundary) {

            var fd = new FormData();
            fd.append(fileInfo.filename, fileInfo.sliceData);
            return fd;
        }

        //SliceInfo: uploadUrl, filename, totalSize, sliceIndex, sliceSize, sliceData
        this.wholeUpload = function (sliceInfo, pparent) {

            if (!pparent)
                return;

            this._parent = pparent;
            //this.sdsParent.sliceState[sliceInfo.sliceIndex] = SliceState.uploading;

            this.xhr = new XMLHttpRequest();
            this.xhr.upload.addEventListener("progress", function (e) { pparent.progress(e, sliceInfo.sliceIndex, this._parent) }, false);
            this.xhr.addEventListener("loadstart", function (e) { pparent.loadstart(e, sliceInfo.sliceIndex, this._parent) }, false);
            this.xhr.addEventListener("error", function (e) { pparent.error(e, sliceInfo.sliceIndex, this._parent) }, false);
            this.xhr.addEventListener("abort", function (e) { pparent.abort(e, sliceInfo.sliceIndex, this._parent) }, false);
            this.xhr.addEventListener("load", function (e) { pparent.load(e, sliceInfo.sliceIndex, this._parent) }, false);

            //var start = sliceInfo.sliceIndex * sliceInfo.sliceSize;
            //var content = start == -1 ? "bytes *" : "bytes " + (start + 1) + "-" + (start + sliceInfo.sliceData.size) + "/" + sliceInfo.totalSize;

            var boundary = this.getBoundary();
            var contentType = "multipart/form-data; boundary=" + boundary;
            var data = this.buildData(sliceInfo, boundary);

            this.xhr.open("POST", sliceInfo.uploadUrl, true)
            this.xhr.setRequestHeader("Pragma", "letv_LCUploader_1.1");
            //this.xhr.setRequestHeader("X_FILENAME", encodeURI(sliceInfo.filename));
            //this.xhr.setRequestHeader("Content-Type", contentType);
            //this.xhr.setRequestHeader("Content-Range", content);

            this.xhr.send(data);
        }


        //SliceInfo: uploadUrl, filename, totalSize, sliceIndex, sliceSize, sliceData
        this.sliceUpload = function (sliceInfo, pparent) {

            if (!pparent)
                return;

            this._parent = pparent;
            this._parent.sliceState[sliceInfo.sliceIndex] = SliceState.uploading;

            this.xhr = new XMLHttpRequest();
            this.xhr.upload.addEventListener("progress", function (e) { pparent.progress(e, sliceInfo.sliceIndex, this._parent) }, false);
            this.xhr.addEventListener("loadstart", function (e) { pparent.loadstart(e, sliceInfo.sliceIndex, this._parent) }, false);
            this.xhr.addEventListener("error", function (e) { pparent.error(e, sliceInfo.sliceIndex, this._parent) }, false);
            this.xhr.addEventListener("abort", function (e) { pparent.abort(e, sliceInfo.sliceIndex, this._parent) }, false);
            this.xhr.addEventListener("load", function (e) { pparent.load(e, sliceInfo.sliceIndex, this._parent) }, false);

            var start = sliceInfo.sliceIndex * sliceInfo.sliceSize;
            var content = start == -1 ? "bytes *" : "bytes " + (start + 1) + "-" + (start + sliceInfo.sliceData.size) + "/" + sliceInfo.totalSize;

            this.xhr.open("POST", sliceInfo.uploadUrl, true)
            this.xhr.setRequestHeader("Pragma", "letv_LCUploader_1.1");
            this.xhr.setRequestHeader("X_FILENAME", encodeURI(sliceInfo.filename));
            this.xhr.setRequestHeader("Content-Range", content);

            this.xhr.send(sliceInfo.sliceData);
        }

        this.xhrAbort = function () {
            this.xhr && this.xhr.abort();
        }
    };
})();


//producer upload
(function () {
    window.Producer = function () {

        this_parent = null;
        this.currentFile = {};
        this.sliceCount = 0;  //slice count of file
        this.sliceState = []; //slice status : -n -> failed count, 0 -> waiting upload, 1 -> upload success
        this.slicePost = []; //slice uploaded size already
        this.sliceSize = 10485760;  //10M
        this.sliceStack = {};

        this.xhr = null;
        this.uploadOption = {};
        this._simpleAjax;

        this.initUrl = "";
        this.uploadUrl = "";
        this.videoId = "";

        this.concurrentCount = 1;
        this.failedCount = 0;
        this.maxFailedCount = 20;
        this.Consumers = [];

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

        this.tryProduce = function(initUrl, file, option, simpleAjax) {

            var data = { videoname: encodeURIComponent(file.name) }
            this._simpleAjax = simpleAjax;
            var argument = this.format(data, {
                client: "html5",
                uploadtype: option.uploadType,
                file_size: file.size,
                t: (new Date).getTime(),
                uc1: option.uc1,
                uc2: option.uc2,
                nodeid: option.nodeid
            })

            this.initUrl = initUrl;
            this.uploadOption = option;
            var _self = this;

            if (window.html5UploadCookie.getItem(option.fileKey)) {
                argument = { token: window.html5UploadCookie.getItem(option.fileKey), uploadtype: option.uploadType };
            }

            simpleAjax.get(initUrl, argument, function (reqData) {
                try {
                    var data = eval("(" + reqData + ")");
                } catch (e) {
                    var data = { code: 999 };
                }

                if (data.code == 0) {
                    var uploadPos = data.data.upload_size || 0;
                    var url = data.data.upload_url.substr(0, data.data.upload_url.length - 10);
                    _self.videoId = data.data.video_id;
                    _self.tryUpload(url, file, uploadPos, option);

                } else {
                    if (_self.uploadOption.init) {
                        _self.uploadOption.init(data);
                    }
                }
            });
        };

        this.tryUpload = function (url, file, transferedsize, option) {

            this._parent = option.pparent;
            this.uploadUrl = url;
            this.currentFile = file;
            this.uploadOption = option;
            this.sliceStack.transferedSize = transferedsize < 0 ? 0 : transferedsize;
            this.sliceStack.startPosition = this.sliceStack.transferedSize;
            this.sliceStack.initTime = 0;

            if (this.uploadOption.uploadType == 0)
                this.sliceSize = file.size;

            this.sliceCount = Math.ceil(file.size / this.sliceSize);  //slice count
            var slicePos = Math.floor(this.sliceStack.startPosition / this.sliceSize); //start upload position

            //resume upload
            for (var index = 0; index < slicePos; index++) {
                this.sliceState[index] = SliceState.success;
                this.slicePost[index] = this.sliceSize;
            }

            for (var index = slicePos; index < this.sliceCount; index++) {
                this.sliceState[index] = SliceState.ready;
                this.slicePost[index] = 0;
            }

            var runCount = Math.min(this.concurrentCount, this.sliceCount - 1);
            runCount = Math.max(runCount, 1);
            for (var index = 0; index < runCount; index++) {

              this.consumerUpload(slicePos + index);
            }

        };

        this.consumerUpload = function(sliceIndex) {
            //SliceInfo: uploadUrl, filename, totalSize, sliceIndex, sliceSize, sliceData

            var consumer = new window.Consumer();
            this.Consumers.push(consumer);

            sliceInfo = {};
            sliceInfo.uploadUrl = this.uploadUrl;
            sliceInfo.filename = this.currentFile.name;
            sliceInfo.totalSize = this.currentFile.size;
            sliceInfo.sliceIndex = sliceIndex;
            sliceInfo.sliceSize = this.sliceSize;
            start = sliceInfo.sliceIndex * sliceInfo.sliceSize;
            sliceInfo.sliceData = this.fileSlice(this.currentFile, start);

            if (this.uploadOption.uploadType == 1) {
                consumer.sliceUpload(sliceInfo, this);
            } else {
                consumer.wholeUpload(sliceInfo, this);
            }
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

        this.load = function (e, sliceIndex, _self) {

          //upload failed , server return stauts != 200
          var res = this.evalTo(e.target.responseText);
          if (!res.totalsize && res.status != '200') {
            if (this.uploadOption.error) {
                this.uploadOption.error(CallbackState.failed, e, this._parent);
            }
            window.html5UploadCookie.removeItem(this.uploadOption.fileKey);
            this.xhrAbort();
            return;
          }

          var tokenUrl = this.uploadUrl.split('token').pop();
          var videoToken = tokenUrl.substr(1, tokenUrl.length - 1).split('&')[0];
          window.html5UploadCookie.setItem(this.uploadOption.fileKey, videoToken, 30);

          this.sliceStack.starttime = (new Date()).getTime();
          if ( this.uploadOption.uploadType == 0 || (res.transferedsize && res.transferedsize == res.totalsize)) {
            if (this.uploadOption.load) {
                window.html5UploadCookie.removeItem(this.uploadOption.fileKey);

                // var argument = {'videoid': this.videoId};
                // var download_url = "";
                // _self = this;
                // this._simpleAjax.get(this.initUrl, argument, function (reqData) {
                //     try {
                //         var data = eval("(" + reqData + ")");
                //     } catch (e) {
                //         var data = { code: 999 };
                //     }

                //     if (data.code == 0) {
                //         download_url = data.data.download_url;
                //         _self.uploadOption.load(CallbackState.success, {'download_url_back': "下载:" + download_url}, _self._parent);
                //     }
                // });

                this.uploadOption.load(CallbackState.success, e, this._parent);
                return;
            }
          }

          this.sliceState[sliceIndex] = SliceState.success;

          //check ready to upload slice
          for (var index = sliceIndex + 1; index < this.sliceCount - 1; index++) {

            if (this.sliceState[index] == SliceState.ready) {
                this.consumerUpload(index);
              return;
            }
          }

          var flag = true;
          for (var index = 0; index < this.sliceCount - 1; index++) {
            if (flag && this.sliceState[index] != SliceState.success) {
              flag = false;
              break;
            }
          }

          //send last slice
          if (flag) {
                this.consumerUpload(this.sliceCount - 1);
            }
        };

        this.loadstart = function (e, sliceIndex, _self) {
          this.sliceStack.initTime = (new Date()).getTime();
        };

        this.progress = function (e, sliceIndex, _self) {
          //
          this.slicePost[sliceIndex] = e.loaded;

          var transferedSize = 0;
          for (var index = 0; index < this.slicePost.length; index++) {
            transferedSize += this.slicePost[index];
          }

          this.sliceStack.transferedSize = transferedSize;

          var pc = parseInt(this.sliceStack.transferedSize / this.currentFile.size * 100);
          var deltaTime = ((new Date()).getTime() - (this.sliceStack.startTime || this.sliceStack.initTime)) / 1000;
          var rate = e.loaded / deltaTime;
          rate = rate / 1024;

          var rateDisplay = rate > 1024 ? (((rate / 1024 * 10) >> 0) / 10).toFixed(1) + "M/s" : (((rate * 10) >> 0) / 10).toFixed(1) + "K/s";

          //delete file while upload file
          if (isNaN(pc)) {
            this.uploadOption.progress(CallbackState.lost, e, this._parent);
            this.xhrAbort();
          } else if (this.uploadOption.progress) {
            this.uploadOption.progress(CallbackState.success, { videoId: this.videoId, progress: pc + "%", speed: rate.toFixed(1), speedDisplay: rateDisplay, file: this.currentFile }, this._parent);
          }
        };

        this.error = function (e, sliceIndex, _self) {
          if (this.failedCount > this.maxFailedCount) {
            if (this.uploadOption.error) {
                this.uploadOption.error(CallbackState.failed, e, this._parent);
            }
            this.xhrAbort();
          } else {
            this.failedCount += 1;
            this.consumerUpload(sliceIndex);
          }
        };

        this.abort = function (e, sliceIndex, _self) {
          if (this.uploadOption.abort) {
                this.uploadOption.abort(e);
            }
        };

        this.xhrAbort = function () {
            for (var index = 0; index < this.Consumers.length; index++) {
                if (this.Consumers[index]) {
                    this.Consumers[index].xhrAbort();
                }
            }
        };
    };
})();

//encapsulate ajax
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
    };

    function Ajax() {
        this.GXHR = null;
        this.CALLBACK = function () { };
        Glib.apply(this)
    };

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

//cookie save token resume
(function () {
    window.html5UploadCookie = {
        setItem: function (key, value, expiresDays) {
            var date = new Date();
            date.setTime(date.getTime() + expiresDays * 24 * 3600 * 1000);
            document.cookie = key + "=" + value + "; expires=" + date.toGMTString();
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
            window.html5UploadCookie.setItem(key, "", -1);
        }
    };
})();


