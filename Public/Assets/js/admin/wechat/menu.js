/**
 * Created by Lin07ux on 2017/1/25.
 */

Vue.config.devtools = true;
Vue.http.options.emulateJSON = true;

new Vue({
    el: '#wx-menu',
    data: {
        loading: false,
        loadMsg: '',
        menus: [],
        form: {},     // 一级菜单添加、编辑表单
        showForm: false,
        subForm: {},  // 二级菜单添加、编辑表单
        currentSub: {},
        showSubForm: false,
        err: { parent: false, sub: false },
        search: { show: false, searching: false, text: '', results: [] }
    },
    computed: {
        addBtnClass: function () {
            var active = this.showForm && this.form.id <= 0;

            return {
                'btn-primary active': active,
                'btn-secondary': !active
            };
        }
    },
    created: function () {
        this.loadMenus();

        this.resetParentMenuForm();
        this.resetSubMenuForm();
    },
    methods: {
        // 获取菜单列表
        loadMenus: function () {
            var self = this;

            this.ajaxGet(urls.lists, {}, '加载菜单列表中...', function (data) {
                // 补全菜单的所有可能的值,避免出现渲染错误
                for (var i = data.length - 1; i >= 0; i--) {
                    if (!data[i].reply) {
                        data[i].reply = { id: 0, msg_type: 0, content: '', news: [] };
                    } else if (!data[i].reply.news) {
                        data[i].reply.news = [];
                    }

                    if (!data[i].sub_button) data[i].sub_button = [];
                }

                self.menus = data;

                if (self.menus.length) {
                    self.form = self.menus[0];
                    self.showForm = true;
                }
            });
        },

        // 顶部标签组的类
        menuClass: function (menu) {
            var active = menu == this.form;

            return {
                'btn-primary active': active,
                'btn-secondary': !active
            };
        },

        // 重置一级菜单表单
        resetParentMenuForm: function () {
            this.form = {
                id: 0,
                ordering: 0,
                type: 0,
                name: '',
                view: '',
                reply: {
                    id: 0,
                    msg_type: 0,
                    content: '',
                    news: []
                },
                sub_button: []
            }
        },

        // 重置二级菜单表单
        resetSubMenuForm: function () {
            this.subForm = {
                id: 0,
                ordering: 0,
                type: 1,
                name: '',
                view: '',
                reply: {
                    id: 0,
                    msg_type: 0,
                    content: '',
                    news: []
                },
                parent: 0
            }
        },

        // 添加一级菜单
        addParentMenu: function () {
            if (this.menus.length >= 3) return alert('一级菜单最多只能有 3 个');

            if (this.form.id > 0) this.resetParentMenuForm();

            this.showForm = true;
        },

        // 编辑一级菜单
        editParent: function (menu) {
            this.form = menu;
            this.showForm = true;
        },

        // 根据菜单类型获取菜单的数据
        getMenuData: function (form) {
            var data = {
                id: form.id,
                type: form.type,
                ordering: form.ordering,
                name: form.name
            };

            if (form.parent) data.parent = form.parent;

            if (1 == form.type) {
                if (!form.view) return alert('请设置跳转网址');

                data.view = form.view;
            } else if (2 == form.type) {
                data.reply = this.getReplyData(form.reply);

                if (!data.reply) return false;
            }

            return data;
        },

        // 根据回复类型获取对应的回复内容
        getReplyData: function (reply) {
            var data = {
                id: reply.id,
                msg_type: reply.msg_type
            };

            switch (+reply.msg_type) {
                case 0:
                    if (!reply.content) {
                        alert('请设置回复的文本内容');
                        return false;
                    }
                    data.content = reply.content;
                    break;
                case 2:
                    if (reply.news.length >= 8) {
                        alert('图文消息的文章最多只能有8个');
                        return false;
                    } else if (!reply.news.length) {
                        alert('请选择图文消息的文章');
                        return false;
                    } else {
                        data.news = [];
                        var length = reply.news.length;
                        for (var i = 0; i < length; i++) {
                            data.news.push(reply.news[i].id);
                        }
                    }
                    break;
                default:
                    alert('消息类别错误');
                    return false;
            }

            return data;
        },

        // 保存一级菜单
        saveParent: function () {
            if (this.err.parent) return false;

            var data = this.getMenuData(this.form);

            if (!data) return false;

            var self = this;
            this.ajaxPost(urls.setMenu, data, '保存中...', function (data) {
                // 添加成功需要存入到 menus 中,修改则不需要处理
                if (!self.form.id) {
                    self.form.id = data;
                    self.menus.push(self.form);

                    alert('添加菜单成功');
                } else {
                    alert('更新菜单信息成功')
                }

                self.sortMenus(self.menus);
            });
        },

        // 检查菜单名称的长度
        // parent (bool) 是否是检查一级菜单的名称长度
        checkName: function (e, parent) {
            var name = e.target.value;
            var length = name.length;
            var count = 0;
            var max = parent ? 8 : 16;

            for (var i = 0; i < length; i++) {
                if (name.charCodeAt(i) > 127 || name.charCodeAt(i) == 94) {
                    count += 2;
                } else {
                    count++;
                }
            }

            if (parent) {
                this.err.parent = count > max;
            } else {
                this.err.sub = count > max;
            }
        },

        // 取消添加一级菜单
        unAddParent: function () {
            if (this.menus.length) {
                this.active = this.menus[0].id;
                this.form = this.menus[0];
            } else {
                this.showForm = false;
            }
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

        // 添加子菜单
        addSubMenu: function () {
            if (this.form.type > 0)
                return alert('该一级菜单不可添加子菜单。如要添加请修改一级菜单的类别。');

            if (!this.form.id) return alert('请先保存一级菜单后再为其添加子菜单');

            this.resetSubMenuForm();
            this.subForm.parent = this.form.id;
            this.showSubForm = true;
        },

        // 编辑子菜单
        editSubMenu: function (sub) {
            this.currentSub = sub;
            var data = Object.assign({}, sub);

            if (data.reply) {
                if ("object" == typeof data.reply) return this.showSubForm = true;

                // 如果子菜单是reply不是对象则说明要获取其对应的详细回复信息
                var msg = '获取子菜单回复详情中...';
                var self = this;

                this.ajaxGet(urls.getReply, { id: sub.id }, msg, function (reply) {
                    data.reply = reply;
                    if (!reply.news) data.reply.news = [];

                    self.subForm = data;
                    self.showSubForm = true;
                });
            } else {
                data.reply = { id: 0, msg_type: 0, content: '', news: [] };
                this.subForm = data;
                this.showSubForm = true;
            }
        },

        // 保存子菜单信息
        saveSub: function () {
            if (this.err.sub) return false;

            var data = this.getMenuData(this.subForm);

            if (!data) return false;

            var self = this;
            this.ajaxPost(urls.setMenu, data, '保存中...', function (id) {
                if (data.reply) {
                    data.reply = true;
                    data.view = '';
                }

                if (data.id > 0) {
                    Object.assign(self.currentSub, data);
                } else {
                    data.id = id;
                    self.form.sub_button.push(data);
                }

                self.sortMenus(self.form.sub_button);

                self.showSubForm = false;
            });
        },

        // 删除菜单
        delMenu: function (isParent) {
            if (!confirm('确定要删除该菜单吗？\n删除后该菜单下设置的所有内容都将被删除。')) return false;

            var self = this;
            var menuId = isParent ? this.form.id : this.currentSub.id;
            this.ajaxPost(urls.remove, { id: menuId }, '删除中...', function(){
                if (isParent) {
                    self.delParentMenu(self.form.id);
                    if (self.menus.length) {
                        self.form = self.menus[0];
                    } else {
                        self.showForm = false;
                        self.resetParentMenuForm();
                    }
                } else {
                    self.delSubMenu(self.subForm.id);
                    self.showSubForm = false;
                }

                alert('删除成功');
            })
        },

        // 删除父菜单
        delParentMenu: function (menuId) {
            for (var i = this.menus.length - 1; i >= 0; i--) {
                if (this.menus[i].id == menuId) {
                    this.menus.splice(i, 1);
                    break;
                }
            }
        },

        // 删除子菜单
        delSubMenu: function (subId) {
            var subs = this.form.sub_button;

            for (var i = subs.length - 1; i >= 0; i--) {
                if (subs[i].id == subId) {
                    subs.splice(i, 1);
                    break;
                }
            }
        },

        // 排序菜单 menus 为菜单数组
        sortMenus: function (menus) {
            // 首先根据 ordering 升序排列
            // ordering 相同的时候根据 id 升序排列
            menus.sort(function (a, b) {
                if (+a.ordering < +b.ordering) return -1;

                if (+a.ordering > +b.ordering) return 1;

                if (+a.id < +b.id) return -1;

                return 1;
            });
        },

        // 发布菜单
        publish: function () {
            if (!confirm('发布后会使用当前设置作为公众号的菜单设置。\n您确定要发布吗？')) return false;

            this. ajaxPost(urls.publish, {}, '发布菜单中...', function (data, msg) {
                alert(msg);
            });
        },

        // ajax get
        ajaxGet : function (url, params, loadMsg, success) {
            this.loadMsg = loadMsg || '';
            this.loading = true;

            var self = this;
            return this.$http.get(url, { params: params}).then(function (response) {
                var res = response.data;

                if (res.code) return alert(res.msg);

                typeof success == 'function' && success(res.data, res.msg);

            }).catch(function () {
                alert('网络故障，请稍后重试！');
            }).then(function () {
                setTimeout(function () { self.loading = false; }, 200);
            });
        },
        // ajax post
        ajaxPost: function (url, body, loadMsg, success) {
            this.loadMsg = loadMsg || '';
            this.loading = true;

            var self = this;
            return this.$http.post(url, body).then(function (response) {
                var res = response.data;

                if (res.code) return alert(res.msg);

                typeof success == 'function' && success(res.data, res.msg);

            }).catch(function () {
                alert('网络故障，请稍后重试！');
            }).then(function () {
                setTimeout(function () { self.loading = false; }, 200);
            });
        }
    }
});