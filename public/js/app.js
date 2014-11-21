(function ( ng, $ ) {
  'use strict';

  ng.module('ngGravityForms', [])
    .value('config', window.NG_GRAVITY_SETTINGS)
    .config (["$httpProvider", function ($httpProvider) {
       $httpProvider.defaults.transformRequest = function (data) {
           if (data == undefined) {
             return data;
           }
           return $.param(data);
       };

       $httpProvider.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded; charset=UTF-8";
    }])
    .controller('FormsCtrl', ['$scope', '$http', '$timeout', 'config', function($scope, $http, $timeout, config){
      $scope.formData = {};
      $scope.showConfirm = false;

      $scope.submitForm = function () {
        $scope.formData.action = config.action;
        $scope.formData.form = config.form;
        $http.post(config.ajaxUrl, $scope.formData).success(function (data) {
          $scope.formData = {};
          $scope.confirm();
        });
      };

      $scope.confirm = function () {
        $scope.showConfirm = true;
        $timeout(function () { $scope.showConfirm = false}, 5000);
      };
    }]);

})( angular, jQuery );
