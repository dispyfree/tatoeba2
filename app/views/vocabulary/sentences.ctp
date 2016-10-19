

<?php
$currentUserName = CurrentUser::get('username');
$title = format(__("{user}'s sentences for vocabulary \"{vocab}\"", true), array(
    'user' => $currentUserName, 
    'vocab' => $vocabulary['Vocabulary']['text']));

$this->set('title_for_layout', $pages->formatTitle($title));
?>

<div id="annexe_content">
    <?php echo $this->element('vocabulary/menu'); ?>
</div>

<div id="main_content">
    <div class="module" ng-controller="VocabularySentencesController as ctrl">
        
    <div class="section md-whiteframe-1dp" md-whiteframe="1">
        <h2><?= $pages->formatTitle($title); ?></h2>
        
        <md-list flex="" role="list" class="flex">            
            <md-list-item  role="listitem" id="sentence_{{sent.id}}" 
                           class="_md" ng-repeat="sent in sentences">
                <img class="vocabulary-lang" src="/img/flags/{{sent.lang}}.png">
                <div class="vocabulary-text flex" flex="">{{sent.text}}</div>

                 <button class="md-icon-button md-button md-ink-ripple" type="button" ng-click="ctrl.remove(sent.id);"
                         ng-disabled="fetchingSuggestions">
                        <md-icon aria-label="<?= __('delete'); ?>" class="ng-scope material-icons"><?= __('delete'); ?></md-icon>
                    </button>
                 <div class="_md-secondary-container"></div>
            </md-list-item>
        </md-list>
        
        <ng-messages for="form.$error" style="color:maroon" role="alert" md-auto-hide="false">
            <ng-message when="ajaxErr">             <?php __('Unknown AJAX error occured'); ?></ng-message>
            <ng-message when="notAssociated">   <?php __('The sentence is not associated with this vocabulary item'); ?></ng-message>
        </ng-messages>
    <p />
    
   <center>
        <md-button type="submit" class="search-submit-button md-raised md-primary"
                   ng-click="ctrl.showSuggestions()">
            <?= __('Show sentence suggestions') ?> 
        </md-button>
    
    
    <p /> 
    <ng-messages for="form.$suggError" style="color:maroon" role="alert" md-auto-hide="false">
        <ng-message when="ajaxErr">             <?php __('Unknown AJAX error occured'); ?></ng-message>
        <ng-message when="alreadyAssociated">   <?php __('The sentence is already associated with this vocabulary item'); ?></ng-message>
        <ng-message when="noneEligible">   <?php __('No eligible sentences could be found'); ?></ng-message>
    </ng-messages>
    
    </center>
    
    <md-list flex="" role="list" class="flex" id="sent_suggestions">            
        <md-list-item  role="listitem" id="sugg_sentence_{{sent.id}}" 
                       class="_md" ng-repeat="sent in suggestions">
            <img class="vocabulary-lang" src="/img/flags/{{sent.lang}}.png">
            <div class="vocabulary-text flex" flex="">{{sent.text}}</div>

             <button class="md-icon-button md-button md-ink-ripple" type="button" ng-click="ctrl.add(sent.id);">
                    <md-icon aria-label="<?= __('add'); ?>" class="ng-scope material-icons">library_add</md-icon>
                </button>
             <div class="_md-secondary-container"></div>
        </md-list-item>
    </md-list>
        
   </div>    
    </div>
</div>

<?php

echo $this->Html->scriptBlock(
        'var globalSentences = '.json_encode($sentences).';'
       .'var globalVocabId = '.$vocabulary['UsersVocabulary']['id'].';'
       .'var globalTerm = '.json_encode($vocabulary['Vocabulary']['text']).';'
       .'var globalLang = '.json_encode($vocabulary['Vocabulary']['lang']).';'
        );
$this->Javascript->link('vocabulary_sentences.js', false);