/**
 * Created by Lin07ux on 2017/3/2.
 */

Vue.http.options.emulateJSON = true;

new Vue({
    el: '#user',
    data: function () { return { form: {} } },
    created: function () {
        this.resetForm();
    },
    methods: {
        resetForm: function () {
            this.form = {
                show: false,
                oldPwd: '',
                newPwd: '',
                rePwd: ''
            };
        },

        // 检查密码修改表单
        check: function () {
            if (!this.form.oldPwd) return '请填写您当前的密码';

            if (!this.form.newPwd) return '请设置新密码';

            if (this.form.newPwd !== this.form.rePwd)
                return '两次输入的新密码不一样，请检查！';

            return null;
        },

        // 修改密码
        submit: function (e) {
            if ($result = this.check()) return alert($result);

            var self = this;
            var data = {
                oldPwd: this.form.oldPwd,
                newPwd: this.form.newPwd
            };

            console.log(data);

            this.$http.post(e.target.action, data).then(function (response) {
                var res = response.data;

                alert(res.msg);

                if (!res.code) self.resetForm();
            });
        }
    }
});