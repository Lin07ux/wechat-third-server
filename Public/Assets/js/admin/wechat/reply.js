/**
 * Created by Lin07ux on 2017/1/16.
 */

Vue.config.devtools = true;
Vue.http.options.emulateJSON = true;

new Vue({
    el: "#reply",
    data: {
        loading: false,
        loadMsg: '',
        tab: 'subscribe',
        subscribe: { news: [] },
        auto: { news: [] },
        keywords: {
            lists: [],
            total: 0,
            pages: 0,
            page: 1,
            rows: 15,
            form: {},
            current: {},
            showForm: false
        },
        news: [],
        search: {
            show: false,
            searching: false,
            text: '',
            results: []
        }
    },
    created: function () {
        this.initData();

        this.resetKeywordForm();
    },
    methods: {
        isText: function (type) { return type == 0; },
        isPic: function (type) { return type == 1; },
        isNews: function (type) { return type == 2; },

        // 初始化回复设置数据
        initData: function () {
            var self = this;
            this.ajaxGet(urls.all, {}, '获取数据中...', function (data) {
                Object.assign(self.subscribe, data.subscribe);
                Object.assign(self.auto, data.auto);
                Object.assign(self.keywords, data.keywords);

                var sub = self.subscribe;
                self.$set(sub, 'is_set', !!(sub.content || sub.news.length));

                var auto = self.auto;
                self.$set(auto, 'is_set', !!(auto.content || auto.news.length));
            });
        },

        // 生成设置回复时提交的数据
        getReplyData: function (reply, isKeyword) {
            var data = {
                id: reply.id,
                type: reply.type,
                msg_type: reply.msg_type
            };

            switch (parseInt(reply.msg_type, 10)) {
                case 0:
                    if (!reply.content) {
                        alert('请设置回复的文本内容');
                        return false;
                    }

                    data.content = reply.content;
                    break;
                case 1:
                    // data.media_id = reply.media_id;
                    alert('暂不支持设置回复消息为图片消息');
                    return false;
                    break;
                case 2:
                    if (reply.news.length >= 8) {
                        alert('图文消息的文章最多只能有8个');
                        return false;
                    } else if (!reply.news.length) {
                        alert('请选择图文消息的文章');
                        return false;
                    } else {
                        data['news'] = [];
                        var length = reply.news.length;
                        if (!length) {
                            alert('请设置图文消息');
                            return false;
                        }

                        for (var i = 0; i < length; i++) {
                            data['news'].push(reply.news[i].id);
                        }
                    }
                    break;
                default:
                    alert('消息类别错误');
                    return false;
            }

            if (isKeyword) {
                if (!reply.keyword) {
                    alert('请填写规则关键词');
                    return false;
                }

                data['keyword'] = reply.keyword;
            }

            return data;
        },
        // 清除设置回复后其他非设置的数据
        resetReplyData: function (reply) {
            switch (parseInt(reply.msg_type, 10)) {
                case 0:
                    reply.news = [];
                    reply.media_id = null;
                    break;
                case 1:
                    // data.media_id = reply.media_id;
                    break;
                case 2:
                    reply.content = '';
                    reply.media_id = null;
                    break;
            }
        },

        // 设置回复消息
        setReply: function (reply, isKeyword) {
            var data = this.getReplyData(reply, isKeyword);
            var self = this;
            var msg = '提交中...';

            this.ajaxPost(urls.setReply, data, msg, function (data) {
                if (isKeyword) {
                    if (reply.id <= 0) {
                        reply.id = data;
                        self.keywords.lists.unshift(Object.assign({}, reply));
                    } else {
                        self.keywords.current.keyword = reply.keyword;
                        self.keywords.current.msg_type = reply.msg_type;
                    }

                    self.keywords.showForm = false;
                } else {
                    if (reply.id <= 0) reply.id = data;
                    reply.is_set = true;

                    self.resetReplyData(reply);
                }

                alert('设置成功');
            });
        },

        // 删除回复
        delReply: function (reply, isKeyword, index) {
            if (!isKeyword && !reply.is_set) return false;

            if (!confirm('确定要删除该回复设置吗？')) return false;

            var data = { id: reply.id, type: reply.type };
            var msg = '删除中...';
            var self = this;

            this.ajaxPost(urls.delReply, data, msg, function () {
                if (isKeyword) {
                    self.keywords.lists.splice(index, 1);
                } else {
                    reply.id = 0;
                    reply.content = '';
                    reply.news = [];
                    reply.is_set = false;
                }
            })
        },

        // 打开搜索文章对话框
        // news 表示选择的结果要放进的地方
        showSearch: function (news) {
            if (news.length >= 8) return alert('每组图文消息最多只能有8条文章');

            this.news = news;
            this.search.show = true;
        },

        // 搜索文章
        doSearch: function () {
            var search = this.search;

            if (!search.text) return false;

            search.searching = true;

            var params = { keyword: search.text };
            this.ajaxGet(urls.search, params, false, function (data) {
                search.results = data;
            }).then(function () {
                setTimeout(function () { search.searching = false }, 200)
            });
        },

        // 选择搜索出来的文章
        select: function (article, index) {
            if (this.news.length >= 8) return alert('每组图文消息最多只能有8条文章');

            // 检查该文章是否设置了标题图片thumb
            if (this.news.length >= 1 && !article.thumb) {
                return alert('该文章未设置标题小图，不可选择！');
            }

            var isExists = false;

            for (var i = this.news.length - 1; i >= 0; i--) {
                if (this.news[i].id == article.id) {
                    isExists = true;
                    break;
                }
            }

            if (isExists) return alert('该文章已存在于图文中了');

            this.news.push(Object.assign({}, article));
            this.search.show = false;
            this.search.results.splice(index, 1);
        },

        // 关键词列表加载及翻页
        loadKeywords: function () {
            var msg = '加载关键词规则列表中...';
            var params = { page: this.keywords.page };
            var self = this;

            this.ajaxGet(urls.keywords, params, msg, function (data) {
                Object.assign(self.keywords, data);
            });
        },
        // 上一页
        prevPage: function () {
            if (this.keywords.page > 1) {
                this.keywords.page--;
                this.loadKeywords();
            }
        },
        // 下一页
        nextPage: function () {
            if (this.keywords.page < this.keywords.pages) {
                this.keywords.page++;
                this.loadKeywords();
            }
        },
        // 跳转页面
        page: function () {
            var keywords = this.keywords;

            if (keywords.page >= 1 && keywords.page <= keywords.pages) {
                this.loadKeywords();
            }
        },

        resetKeywordForm: function () {
            this.keywords.form = {
                id: 0,
                type: 2,
                msg_type: 0,
                keyword: '',
                content: '',
                media_id: null,
                news: []
            };
        },

        // 生成消息类型对应的文本
        msgType: function (msg_type) {
            var name;

            switch (+msg_type) {
                case 0:
                    name = '文本';
                    break;
                case 1:
                    name = '图片';
                    break;
                case 2:
                    name = '图文';
                    break;
                default :
                    name = '';
            }

            return name;
        },

        addKeyword: function () {
            this.resetKeywordForm();
            this.keywords.showForm = true;
        },

        // 编辑关键词回复
        editKeyword: function (keyword) {
            var kw = this.keywords;
            kw.current = keyword;

            var params = { id: keyword.id };
            var msg = '加载规则详情中...';
            this.ajaxGet(urls.keywordDetail, params, msg, function (data) {
                Object.assign(kw.form, data);
                kw.showForm = true;
            });
        },

        // ajax get
        ajaxGet : function (url, params, loadMsg, success) {
            if (loadMsg) {
                this.loadMsg = loadMsg;
                this.loading = true;
            }

            var self = this;

            return this.$http.get(url, { params: params}).then(function (response) {
                var res = response.data;

                if (res.code) return alert(res.msg);

                typeof success == 'function' && success(res.data);

            }).catch(function () {
                alert('网络故障，请稍后重试！');
            }).then(function () {
                setTimeout(function () { self.loading = false; }, 200);
            });
        },
        // ajax post
        ajaxPost: function (url, body, loadMsg,  success) {
            if (loadMsg) {
                this.loadMsg = loadMsg;
                this.loading = true;
            }

            var self = this;

            return this.$http.post(url, body).then(function (response) {
                var res = response.data;

                if (res.code) return alert(res.msg);

                typeof success == 'function' && success(res.data);

            }).catch(function () {
                alert('网络故障，请稍后重试！');
            }).then(function () {
                setTimeout(function () { self.loading = false; }, 200);
            });
        }
    }
});