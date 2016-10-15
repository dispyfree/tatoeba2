<?php
/**
 * Tatoeba Project, free collaborative creation of multilingual corpuses project
 * Copyright (C) 2016  HO Ngoc Phuong Trang <tranglich@gmail.com>
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
 *
 * PHP version 5
 *
 * @category PHP
 * @package  Tatoeba
 * @author   HO Ngoc Phuong Trang <tranglich@gmail.com>
 * @license  Affero General Public License
 * @link     http://tatoeba.org
 */

/**
 * Controller for vocabulary.
 *
 * @category Vocabulary
 * @package  Controllers
 * @author   HO Ngoc Phuong Trang <tranglich@gmail.com>
 * @license  Affero General Public License
 * @link     http://tatoeba.org
 */
class VocabularyController extends AppController
{
    //weird bug: order of UsersVocabulary and UsersVocabularySentences matters
    public $uses = array( 'UsersVocabulary',
        'UsersVocabularySentences', 'User', 'Sentence', 'Vocabulary');
    public $components = array ('CommonSentence', 'Session');

    /**
     * Before filter.
     *
     * @return void
     */
    public function beforeFilter()
    {
        parent::beforeFilter();

        // setting actions that are available to everyone, even guests
        $this->Auth->allowedActions = array('of');
    }
    
    /**
     * Page that lists all the vocabulary items of given user in given language.
     *
     * @param $username string Username of the user.
     * @param $lang     string Language of the items.
     */
    public function of($username, $lang = null)
    {
        $this->helpers[] = 'Pagination';
        $this->helpers[] = 'CommonModules';

        $username = Sanitize::paranoid($username, array('_'));
        
        $userId = $this->User->getIdFromUsername($username);

        if (!$userId) {
            $this->Session->setFlash(format(
                __('No user with this username: {username}', true),
                array('username' => $username)
            ));
            $this->redirect(
                array('controller'=>'users',
                  'action' => 'all')
            );
        }

        $this->paginate = $this->UsersVocabulary->getPaginatedVocabularyOf(
            $userId,
            $lang
        );

        $vocabulary = $this->Vocabulary->syncNumSentences($this->paginate());

        $this->set('vocabulary', $vocabulary);
        $this->set('username', $username);
        $this->set('canEdit', $username == CurrentUser::get('username'));
    }


    /**
     * Page where users can add new vocabulary items to their list.
     */
    public function add()
    {
    }


    /**
     * Page that lists all the vocabulary items for which sentences are wanted.
     *
     * @param $lang string Language of the vocabulary items.
     */
    public function add_sentences($lang = null)
    {
        $this->helpers[] = 'Pagination';
        $this->helpers[] = 'CommonModules';
        $this->helpers[] = 'Languages';

        $this->paginate = $this->Vocabulary->getPaginatedVocabulary($lang);
        $vocabulary = $this->paginate('Vocabulary');

        $this->set('vocabulary', $vocabulary);
        $this->set('langFilter', $lang);
    }


    /**
     * Saves a vocabulary item.
     */
    public function save()
    {
        $lang = $_POST['lang'];
        $text = $_POST['text'];

        $result = $this->Vocabulary->addItem($lang, $text);
        $hexValue = unpack('H*', $result['id']);
        $result['id'] = str_pad($hexValue[1], 32, '0');
        $numSentences = $result['numSentences'];
        $result['numSentencesLabel'] = format(
            __n('{number} sentence', '{number} sentences', $numSentences, true),
            array('number' => $numSentences)
        );
        $this->set('result', $result);

        $this->layout = 'json';
        
        // Save last added voacbulary in session
        // may be used for for autocomplete when linking that newly added vocab
        $this->Session->write('lastVocabulary', array(
            'id'        => $result['userVocabId'],
            'display'   => $result['text']
        ));
    }


    /**
     * Removes vocabulary item of given id.
     *
     * @param $id int Hexadecimal value of vocabulary id.
     */
    public function remove($id)
    {
        $vocabularyId = hex2bin($id);

        $data = $this->UsersVocabulary->find(
            'first',
            array(
                'conditions' => array(
                    'vocabulary_id' => $vocabularyId,
                    'user_id' => CurrentUser::get('id')
                )
            )
        );

        if ($data) {
            $id = $data['UsersVocabulary']['id'];
            $this->UsersVocabulary->delete($id, false);
        }

        $this->set('vocabularyId', array('id' => $id));

        $this->layout = 'json';
    }


    /**
     * Saves a sentence for vocabulary of given id and updates the count of
     * sentences for that vocabulary item.
     *
     * @param int $vocabularyId Hexadecimal value of the vocabulary id.
     */
    public function save_sentence($vocabularyId)
    {
        $sentenceLang = $_POST['lang'];
        $sentenceText = $_POST['text'];
        $userId = CurrentUser::get('id');
        $username = CurrentUser::get('username');

        $isSaved = $this->CommonSentence->addNewSentence(
            $sentenceLang,
            $sentenceText,
            $userId,
            $username
        );

        $sentence = null;

        if ($isSaved) {
            $isDuplicate = $this->Sentence->duplicate;

            if (!$isDuplicate) {
                $numSentences = $this->Vocabulary->incrementNumSentences(
                    $vocabularyId,
                    $sentenceText
                );
            }

            $sentence = array(
                'id' => $this->Sentence->id,
                'text' => $sentenceText,
                'duplicate' => $isDuplicate
            );
        }

        $this->set('sentence', $sentence);

        $this->layout = 'json';
    }
    
    /**
     * Searches vocabulary items with the given string (which is json_encoded).
     * The result will be output in JSON in the following form
     * {
     *  id:         the id of that vocabulary item 
     *  display:    the name of the vocabulary item
     *  numAdded:   number of associated sentences
     * }
     * Terms with length  < 3 will be ignored for performance reasons
     * @param string data[term] The term to search for (POST)
     * @return  string  a JSON encoded list (see above)
     */
    public function search()
    {
        $term = json_decode(@$this->data['term']);
        $userId = CurrentUser::get('id');
        $results = array();
        
        if(strlen($term) >= 3){
            $results = $this->UsersVocabulary
                ->find('all', 
                      array('conditions' => array(
                        'UsersVocabulary.user_id ' => $userId,
                        'Vocabulary.text LIKE' => $term.'%'), 
                        'contain' => 'Vocabulary'));
            
            array_walk($results, function(&$obj){
                $res = array(
                    'id'        => $obj['UsersVocabulary']['id'],
                    'display'   => $obj['Vocabulary']['text'],
                    'numAdded'  => $obj['Vocabulary']['numAdded']
                    );
                $obj = $res;
            });
        }
             
        $this->autoRender = false;
        return json_encode($results);
    }
    
    /**
     * Manages association of a sentence with vocabulary
     * The post-data has the following form (data[sentence_id] etc.)
     * data{
     *  sentence_id:         the id of that vocabulary item 
     *  (users)vocabulary_id:    the id of the vocabulary item (if it exists)
     *  action:   number of associated sentences (associate or unassociate),
     *  vocabText:  if vocab item is created on the fly, supply text
     * }
     * @return array    ['success' : boolean, 'errMsg': string] JSON encoded
     */
    public function handleAssociation()
    {
        $userId         = CurrentUser::get('id');
        $sentenceId     = json_decode(@$this->data['sentence_id']);
        $vocabularyId   = json_decode(@$this->data['vocabulary_id']);
        $action         = @$this->data['action'];
        $vocabText      = json_decode(@$this->data['vocabText']);
        
        $this->autoRender = false;
        $uVocabulary = null;
        
        try{
            //Check if action is valid 
            if (!in_array($action, array('associate', 'unassociate'))) {
                    throw new InvalidArgumentException(__('Invalid action specified'));
            }
            
            //Check if data actually exists
            $sentence = $this->Sentence->findById($sentenceId);
            
            if($vocabularyId)
                $uVocabulary = $this->UsersVocabulary->findById($vocabularyId);
            else
                $uVocabulary = $this->UsersVocabulary->getOwnByText($vocabText);

            //Shall we create it just in time? 
            if($action == 'associate' && !$uVocabulary){
                $lang = $sentence['Sentence']['lang'];
                $data = $this->Vocabulary->addItem($lang, $vocabText);
                
                $action = 'associate';
                $vocabularyId = $data['userVocabId']; 
            }
            if(!$uVocabulary)
                $uVocabulary = $this->UsersVocabulary->findById($vocabularyId);

            if (!isset($sentence['Sentence']) || !isset($uVocabulary['UsersVocabulary'])) {
                throw new InvalidArgumentException(__('Invalid sentence or vocabulary'));
            }
            
            //Check if access is to be granted
            if ($uVocabulary['UsersVocabulary']['user_id'] != $userId) {
                throw new InvalidArgumentException( __('Access denied'));
            }
            
            
            if ($action == 'associate') {
               $this->UsersVocabularySentences->associate($sentence, $uVocabulary);
            }
            else{
                $this->UsersVocabularySentences->unAssociate($sentence, $uVocabulary);
            }
            
        } catch (Exception $ex) {
            $ret = array('success' => false, 'errMsg' => $ex->getMessage());
            return json_encode($ret);
        }
        
        $ret = array('success' => true, 'userVocabId' => $uVocabulary['UsersVocabulary']['id']);
        return json_encode($ret);
    }
    
    /**
     * Lists all sentences associated with the given UsersVocabulary
     * 
     * @param int $id   id of the UsersVocabulary item
     */
    public function sentences($id){
         $this->helpers[] = 'Pagination';
         
         $sentences = $this->Sentence->findByUserVocabulary($id);
         $this->UsersVocabulary->contain('Vocabulary');
         $vocabulary = $this->UsersVocabulary->findById($id);
         
         //Perform mapping
         $res = array();
         foreach($sentences as $sentence){
             $res[] = array(
                 'id'   => $sentence['Sentence']['id'],
                 'text' => $sentence['Sentence']['text'],
                 'lang' => $sentence['Sentence']['lang']
             );
         }
         
         $this->set('sentences', $res);
         $this->set('vocabulary', $vocabulary);
    }
}
?>