<?php

/**
 * Wyzant Search API Datasource for CakePHP
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * @author Chris Vogt <@c1v0>
 * @copyright (c) 2013 Chris Vogt
 * @license http://opensource.org/licenses/MIT
 * @link http://github.com/chrisvogt
 * @link http://chrisvogt.me
 * @since 0.9.0
 */
App::uses('HttpSocket', 'Network/Http');
App::import('Xml', 'String', 'Core');

class WyzantSearchSource extends DataSource {

/**
 * Class constructor
 * 
 * @param array $config
 */
    public function __construct($config) {
        $config['baseUrl'] = 'http://data.wyzant.com/api/search';
        parent::__construct($config);
        $this->Http = new HttpSocket();
    }

/**
 * Overrides and clears listSources()
 * 
 * @param object $data
 * @return null
 */
    public function listSources($data = null) {
        return null;
    }

/**
 * Powers find()
 * 
 * @param Model $model
 * @param array $queryData
 * @param integer $recursive
 * @return array
 * @throws CakeException
 */
    public function read(Model $model, $queryData = array(), $recursive = null) {
        if (!empty($queryData['queryParams'])) {
            $queryParams = http_build_query($queryData['queryParams']);
        }
        
        $path = $this->config('baseUrl') . '?PartnerAPIKey='
                        . $this->config['PartnerAPIKey']
                        . '&ShareASaleID='
                        . $this->config['ShareASaleID']
                        . '&' . $queryData['queryParams']), true;
        );

        $sanitizedPath = str_replace('/', '-', strtolower($path));
        $result = Cache::read($sanitizedPath, 'wyzant_search');
        
        if (!$result) {
            $result = json_decode($this->Http->get($path);
            if (is_null($result)) {
                $error = json_last_error();
                throw new CakeException($error);
            }
            $this->_decode($result);
            Cache::set(array('duration' => '+5 minutes'));
            Cache::write('sanitizedPath', $result, 'wyzant_search');
        }
        if ($queryData['fields'] === 'COUNT') { # Counts, as instructed by calculate()
            return array(array(array('count' => count($items))));
        }

        return($array);
    }

/**
 * Determines how to count the records
 *
 * Required to get ``update()`` and ``delete()`` to work.
 *
 * Returns a string to be passed to ``read()`` which will do the
 * actual counting. The easiest way is to just return the string
 * 'COUNT' and check for it in ``read()``where ``$data['fields'] === 'COUNT'``.
 */
    public function calculate(Model $model, $func, $params = array()) {
        return 'COUNT';
    }

/**
 * Decodes the Wyzant Search response
 * 
 * @param array $items
 */
    public function _decode($items) {
        $i = 0;
        $array = array();
        $subjects = array();
        foreach ($items as $item) {

            if (!isset($item['TutorPictures'][0])) {
                $item['TutorPictures'][0] = 'http://s3.wyzant.com/userfiles/wyzfiles/reference/default.gif';
            }

            if (!isset($item['Subjects']) || !is_array($item['Subjects'])) {
                $item['Subjects'] === null;
            }

            $array[] = array(
                'id' => $item['TutorID'],
                'name' => $item['Name'],
                'city' => $item['City'],
                'state' => $item['State'],
                'zip' => $item['Zip'],
                'title' => $item['Title'],
                'fee_per_hour' => $item['FeePerHour'],
                'free_response' => $item['FreeResponse'],
                'travel_distance' => $item['TravelDistance'],
                'profile_image_url' => $item['TutorPictures'][0],
                'profile_url' => $item['ProfileLink'],
                'email_url' => $item['EmailLink'],
                'subjects' => $item['Subjects'],
                'reviews' => $item['Reviews'],
                'star_rating_average' => $item['StarRatingAverage'],
                'star_rating_count' => $item['StarRatingCount'],
                'tutor_rank' => $item['TutorRank'],
                'college' => $item['College']
            );

            $i++;
        }
    }

}