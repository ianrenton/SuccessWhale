// Create app
SuccessWhale = Ember.Application.create();

// Create Ember Data store for handling models
SuccessWhale.Store = DS.Store.extend({
  revision: 12
});

// Set up API proxy
DS.RESTAdapter.reopen({
  namespace: 'apiproxy'
});

// Set up routes
SuccessWhale.Router.map(function() {
  this.route("login");
  this.route("logout");
});

// Test index route
SuccessWhale.IndexRoute = Ember.Route.extend({
  model: function() {
    return ['red', 'yellow', 'blue'];
  }
});

// Auth Data model
SuccessWhale.AuthData = DS.Model.extend({
  sw_uid: DS.attr('string'),
  secret: DS.attr('string'),
});