(function (){
  'use strict';

  window.Drafts = Ember.Application.create({
    rootElement: '.wrap',
    LOG_TRANSITIONS: true,
    LOG_TRANSITIONS_INTERNAL: true
  });

  Drafts.Router.reopen({
    location: 'none'
  });

  Drafts.Router.map(function() {
    // put your routes here
  });

  Drafts.IndexRoute = Ember.Route.extend({
    model: function() {
      return ['red', 'yellow', 'blue', 'sparkly'];
    }
  });

})();
