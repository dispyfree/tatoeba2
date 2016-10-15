/**
 * Tatoeba Project, free collaborative creation of multilingual corpuses project
 * Copyright (C) 2014  Gilles Bedel
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
(function() {
    'use strict';
    
    angular.module('app')
    .controller('VocabularySentencesController',['$http', '$scope', '$timeout', function($http, $scope, $timeout){
        $scope.sentences = globalSentences;
        $scope.suggestions = [];
        
        //This is the UserVocabulary ID!
        $scope.vocabId   = globalVocabId;  
        $scope.term = globalTerm;
        
        $scope.ajaxUnderway = false;
        $scope.fetchingSuggestions = false;
        
        $scope.form = {
            "$error" : {},
            "$suggError" : {}            
            };
        
        var that = this;
        this.remove = function(sentenceId){
            return $http({
                method: 'POST',
                url: '/vocabulary/handleAssociation',
                data:{
                'data[action]'          : 'unassociate',
                'data[sentence_id]'     : sentenceId,
                'data[vocabulary_id]'   : JSON.stringify($scope.vocabId)
                }
                }).then(function successCallback(response) {
                    if(response.data.success == false){
                        $scope.form['$error'][response.data.errMsg] = true;
                    }
                    else{          
                        that.removeFromArr($scope.sentences, function(tmpObj){
                            return tmpObj.id == sentenceId;
                        })                    
                    }
                    return response.data;
                  }, function errorCallback(response) {
                      $scope.form['$error']['ajaxErr'] = true;
                  });
        }
        
        this.removeFromArr = function(arr, f){
            for(var index = 0; index < arr.length; index++){
                if (f(arr[index])) {
                  // remove the matching item from the array
                  return arr.splice(index, 1)[0];
                }
            };
        }
        
         this.removeAllFromArr = function(arr, f){
            for (var index = arr.length - 1; index >= 0; index--) {
                if (f(arr[index])) {
                    // remove the matching item from the array
                    arr.splice(index, 1);
                }
            }
            return arr;
        }
        
        this.contains = function(arr, f){
            for(var index = 0; index < arr.length; index++){
                if(f(arr[index])){
                    return true;
                }
            }
            return false;
        }
        
        this.showSuggestions = function(){  
            $scope.fetchingSuggestions = true;
            //toggle if suggestions are already being shown 
            if($scope.suggestions.length > 0){
                $("#sent_suggestions").toggleClass("ng-hide");
                return;
            }

            return $http({
                method: 'POST',
                url: '/sentences/suggestions',
                data:{
                'data[term]'          : $scope.term
                }
                }).then(function successCallback(response) {
                    $scope.fetchingSuggestions = false;
                    $scope.suggestions = response.data;
                    
                    //Don't show suggestions which have already been added
                    that.removeAllFromArr($scope.suggestions, function(tmpSugg){
                        return that.contains($scope.sentences, function(tmpSent){
                            return tmpSent.id == tmpSugg.id;
                        });
                    });
                    
                    //Display error messsage if there are no matching sentences
                    if($scope.suggestions.length == 0){
                        $scope.form['$suggError']['noneEligible'] = true;
                    }
                    return response.data;
                    
                  }, function errorCallback(response) {
                      $scope.fetchingSuggestions = false;
                      $scope.form['$suggError']['ajaxErr'] = true;
                  });  
        }
        
        this.add = function(sentId){
            return $http({
                method: 'POST',
                url: '/vocabulary/handleAssociation',
                data:{
                    'data[action]'          : 'associate',
                    'data[sentence_id]'     : sentId,
                    'data[vocabulary_id]'   : $scope.vocabId
            }}).then(function successCallback(response) {
                if(response.data.success == false){
                    $scope.form['$suggError'][response.data.errMsg] = true;
                }
                else{
                    $scope.form['$suggError'] = {};
                    $timeout(function() {
                        var row = that.removeFromArr($scope.suggestions, function(obj){
                            return obj.id == sentId;
                        });
                        $scope.sentences.push(row);
                    });
                }   
                
                return response.data;
              }, function errorCallback(response) {
                  $scope.form['$suggError']['ajaxErr'] = true;
              });
        }
    }])
})();
    