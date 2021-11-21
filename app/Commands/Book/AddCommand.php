<?php

namespace App\Commands\Book;

use App\Database;
use App\Utility\RecordUtility;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Question\Question;

class AddCommand extends Command
{
    protected $signature = 'book:add';

    protected $description = 'Add a book';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Database $db)
    {
        $authors = $this->askForAuthors($db);
        var_dump($authors);

        return;

        $title = $this->ask('Title?');
        $key = $this->ask('Key?', RecordUtility::createKey($authors[0], $title));

        $db->getBooks()->insert([
            'key' => $key,
            'title' => $title,
            'authors' => $authors,
        ]);
    }

    protected function askForAuthors(Database $db): array
    {
        $authors = [];

        while (true) {
            // Fetch all persons and build two autocomplete options per person:
            // {firstname} {lastname}
            // {lastname}, {firstname}
            $persons = $db->getPersons()->createQueryBuilder()
                ->select(['key', 'firstname', 'lastname'])
                ->getQuery()
                ->fetch();
            $options = [];
            foreach ($persons as $person) {
                $options["{$person['firstname']} {$person['lastname']}"] = $person['key'];
                $options["{$person['lastname']}, {$person['firstname']}"] = $person['key'];
            }

            // Prepare the question.
            $question = new Question('Author?');
            $question->setAutocompleterValues(array_keys($options));

            // Normalize the user selection:
            // - First array element is true if the input was in the autocomplete options.
            // - Second array element is a string.
            //   If the first element is true, it's the person key. Otherwise it's the user input (may be null).
            $question->setNormalizer(function ($value) use ($options) {
                if (array_key_exists($value, $options)) {
                    return [true, $options[$value]];
                } else {
                    return [false, $value];
                }
            });

            // Prompt the user and get the selection.
            list($personExists, $value) = $this->output->askQuestion($question);

            if ($personExists) {
                // If the person exists, use it.
                $author = $value;
            } elseif (!$value) {
                // If the person doesn't exists and the input is empty, exit the loop.
                break;
            } else {
                // If the selected person doesn't exist, ask if it should be created.
                if (!$this->confirm('The selected person doesn\'t exist. Do you want to create it?', true)) {
                    // If the user doesn't want to add a person, exit the loop.
                    break;
                } else {
                    // Create a new person.
                    // Create default values for first and last name.
                    if (str_contains($value, ',')) {
                        // If the user input contains a comma: use the first part as lastname and the rest as firstname.
                        list($lastname, $firstname) = array_map('trim', explode(',', $value, 2));
                    } else {
                        // Otherwise use everything before the last space as firstname and the rest as lastname.
                        $parts = explode(' ', $value);
                        $lastname = trim(array_pop($parts));
                        $firstname = trim(implode(' ', $parts));
                    }

                    // Prompt for person data and persist.
                    $firstname = $this->ask('First name?', $firstname);
                    $lastname = $this->ask('Last name?', $lastname);
                    // TODO lastname must not be empty!
                    $nationality = $this->ask('Nationality?');
                    $key = $this->ask('Key?', RecordUtility::createKey($firstname, $lastname));
                    // TODO key must not be empty!
                    $db->getPersons()->insert([
                        'key' => $key,
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'nationality' => $nationality,
                    ]);
                    $author = $key;
                }
            }
            $authors[] = $author;
        }

        return $authors;
    }
}
