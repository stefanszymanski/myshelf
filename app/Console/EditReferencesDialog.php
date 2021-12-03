<?php

declare(strict_types=1);

namespace App\Console;

use App\Persistence\Database;
use App\Persistence\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// TODO add a breadcrumb-like overview, e.g. "Edit book 4" > "Edit author references" > "Add person reference" > "Create new person"
//      I think that's necessary, because the different edit/create dialogs can be nested deeply
class EditReferencesDialog
{
    public function __construct(protected InputInterface $input, protected SymfonyStyle $output, protected Database $db, protected Table $table)
    {
    }

    /**
     * Render a references edit dialog.
     *
     * @param array<string> $keys
     * @return array<string>
     */
    public function render(array $keys): array
    {
        // TODO action for moving a line up/down?
        $elements = array_map(fn ($answer) => [$answer, $answer], $keys);
        do {
            $exit = false;
            $action = empty($elements)
                ? 'n'
                : $this->output->ask("Enter action [#,d#,r#,a,d!,r!,s,w,q,q!,?]");
            switch ($action) {
                case '?':
                    // Display help.
                    $this->displayHelp();
                    break;
                case 's':
                    // Display elements.
                    $this->displayList($elements);
                    break;
                case 'w':
                case 'wq':
                    // Keep changes to the list and exit.
                    $exit = true;
                    break;
                case 'q':
                    // If the elements were changed: ask if the changes should be saved
                    // If the elements weren't changed: just exit
                    if (array_column($elements, 0) !== array_column($elements, 1)) {
                        switch (strtolower($this->output->ask('Save changes? (Y)es, (N)o, [C]ancel', 'C'))) {
                            case 'y':
                                $exit = true;
                                break;
                            case 'n':
                                $elements = $this->resetList($elements);
                                $exit = true;
                                break;
                            default:
                                // do nothing
                        }
                    } else {
                        $exit = true;
                    }
                    break;
                case 'd!':
                    // Remove all added references and mark the initial ones for deletion.
                    $elements = $this->clearList($elements);
                    break;
                case 'q!':
                    // Reset the list to its initial state and exit.
                    $elements = $this->resetList($elements);
                    $exit = true;
                    break;
                case 'r!':
                    // Reset the list to its initial state.
                    $elements = $this->resetList($elements);
                    break;
                case 'a':
                case 'n':
                case 'c':
                    // Add a new reference to the list.
                    $elements = $this->addToList($elements);
                    break;
                default:
                    // Edit, remove or restore an element
                    list($action, $elementNumber) = $this->parseElementAction($action, $elements);
                    if (!$action) {
                        $this->output->error('Invalid action or element');
                        break;
                    }
                    switch ($action) {
                        case 'e':
                            $elements = $this->editElement($elements, $elementNumber);
                            break;
                        case 'd':
                            $elements = $this->removeElement($elements, $elementNumber);
                            break;
                        case 'r':
                            $elements = $this->restoreElement($elements, $elementNumber);
                            break;
                        default:
                            $this->output->error('Invalid action');
                            break;
                    }
            }
        } while (!$exit);
        return array_filter(array_column($elements, 1));
    }

    /**
     * Display information about available actions and their keys.
     *
     * @return void
     */
    protected function displayHelp(): void
    {
        $lines = [
            ' # - edit reference #',
            'd# - delete reference #',
            'r# - restore reference # to its original value',
            ' a - add a reference, also [c,n]',
            'd! - delete all references',
            'r! - restore all references to their original state',
            ' s - show references',
            ' w - save changes and quit, also [wq]',
            ' q - quit (asks for confirmation if there are changes)',
            'q! - quit without saving',
            ' ? - print help',
        ];
        $this->output->text($lines);
    }

    /**
     * Display the elements as table.
     *
     * Highlights whether elements were deleted, added or replaced.
     *
     * @param array<array{string|null,string|null}> $list
     * @return void
     */
    protected function displayList(array $list): void
    {
        // TODO render a prettier table
        // TODO display headlines in blue, not only here but everywhere.
        //      The default green should only be used for indicating new values
        //      Or maybe another color than blue?
        $rows = [];
        for ($i = 0; $i < sizeof($list); $i++) {
            list($old, $new) = $list[$i];
            $format = match (true) {
                $old === $new => '%1$s',
                $old !== null && $new === null => '<fg=red>%1$s</>',
                $old === null && $new !== null => '<fg=green>%2$s</>',
                $old !== $new => '<fg=red>%1$s</> <fg=green>%2$s</>',
                default => throw new \UnexpectedValueException('This should not happen'),
            };
            $rows[] = [$i + 1, sprintf($format, $old, $new)];
        }
        $this->output->table(['#', 'Values'], $rows);
    }

    /**
     * Display a record selection dialog.
     *
     * @param string|null $key Default answer for the record selection dialog
     * @return string|null Key of the selected record
     */
    protected function askForRecord(?string $key = null): ?string
    {
        return (new RecordSelector($this->input, $this->output, $this->db, $this->table))->render($key);
    }

    /**
     * Reset the list to its initial state.
     *
     * @param array<array{string|null,string|null}> $list
     * @return array<array{string|null,string|null}> Updated list
     */
    protected function resetList(array $list): array
    {
        return array_map(
            fn ($element) => [$element[0], $element[0]],
            array_filter($list, fn ($element) => $element[0] !== null)
        );
    }

    /**
     * Mark the initial elements for deletion and remove all non-initial elements.
     *
     * @param array<array{string|null,string|null}> $list
     * @return array<array{string|null,string|null}> Updated list
     */
    protected function clearList(array $list): array
    {
        return array_map(
            fn ($element) => [$element[0], null],
            array_filter($list, fn ($element) => $element[0] !== null)
        );
    }

    /**
     * Add a new reference.
     *
     * @param array<array{string|null,string|null}> $list
     * @return array<array{string|null,string|null}> Updated list
     */
    protected function addToList(array $list): array
    {
        $newValue = $this->askForRecord();
        if ($newValue) {
            if (!in_array($newValue, array_column($list, 1))) {
                if (in_array($newValue, array_column($list, 0))) {
                    $n = array_search($newValue, array_column($list, 0));
                    $this->output->warning("Record $newValue was already selected, but marked for deletion");
                    $list[$n] = [$newValue, $newValue];
                } else {
                    $list[] = [null, $newValue];
                }
            } else {
                $this->output->warning("Record $newValue is already selected");
            }
        }
        return $list;
    }

    /**
     * Parse an element specific action into action string and element number.
     *
     * Examples:
     *   '3' => ['e', 3]
     *   'd12' => ['d', 12]
     *
     * Returns [null, null] if the element number is invalid, i.e. < 1 or > the number of elements.
     *
     * @param string $action
     * @param array<array{string|null,string|null}> $list
     * @return array{string|null,int|null} Next action and number of the selected reference
     */
    protected function parseElementAction(string $action, array $list): array
    {
        if (ctype_digit($action)) {
            $fieldNumber = $action;
            $action = 'e';
        } else {
            $fieldNumber = substr($action, 1);
            $action = substr($action, 0, 1);
        }
        return !ctype_digit($fieldNumber) || (int) $fieldNumber < 1 || (int) $fieldNumber > sizeof($list)
            ? [null, null]
            : [$action, (int) $fieldNumber - 1];
    }

    /**
     * Edit a reference.
     *
     * @param array<array{string|null,string|null}> $list
     * @param int $elementNumber
     * @return array<array{string|null,string|null}> Updated list
     */
    protected function editElement(array $list, int $elementNumber): array
    {
        $newValue = $this->askForRecord($list[$elementNumber][1]);
        if (!$newValue) {
            $this->output->warning('Nothing was selected, selection was dismissed');
        } else {
            $list[$elementNumber][1] = $newValue;
        }
        return $list;
    }

    /**
     * Restore a reference.
     *
     * @param array<array{string|null,string|null}> $list
     * @param int $elementNumber
     * @return array<array{string|null,string|null}> Updated list
     */
    protected function restoreElement(array $list, int $elementNumber): array
    {
        if ($list[$elementNumber][0] !== null) {
            $list[$elementNumber][1] = $list[$elementNumber][0];
        }
        return $list;
    }

    /**
     * Remove a reference.
     *
     * @param array<array{string|null,string|null}> $list
     * @param int $elementNumber
     * @return array<array{string|null,string|null}> Updated list
     */
    protected function removeElement(array $list, int $elementNumber): array
    {
        if ($list[$elementNumber][0] !== null) {
            $list[$elementNumber][1] = null;
        } else {
            unset($list[$elementNumber]);
        }
        return $list;
    }
}
