$(function(){
    var AuthModel = Backbone.Model.extend({
        urlRoot: '/apiproxy/authenticate',
        defaults: {
            sw_uid: '',
            secret: ''
        }
    });

    var authUser = new AuthModel();

    authUser.fetch({
        data: {username: 'tsuki_chama', password: 'XXXXXXX'},
        success: function (authUser) {
            alert(JSON.stringify(authUser.toJSON()));
        },
        error: function (model, response, options) {
            alert(response.responseText);
        }
    });
});