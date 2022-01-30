<?php

declare(strict_types=1);

namespace App\Console;

use App\Context;
use App\Persistence\Data\ListField;
use App\Validator\IntegerValidator;

class EditListDialog extends Dialog
{
    protected ListField $field;

    public function __construct(protected Context $context)
    {
        $this->output = $context->output;
        $this->db = $context->db;
    }

    /**
     * Render a list edit dialog.
     *
     * @param array<string> $values
     * @return array<string>
     */
    public function render(ListField $field, array $values): array
    {
        $this->field = $field;
        $elements = array_map(fn ($answer) => [$answer, $answer], $values);

        // Add a layer that prints the elements list on each update.
        $layer = $this->context->addLayer(
            sprintf('Edit list "%s"', $this->field->getLabel()),
            function() use (&$elements) {
                $this->displayList($elements);
            },
        );

        do {
            $exit = false;

            // Update the layer and ask for an action.
            $layer->update();
            $actions = $this->field->sortable
                ? '[#,s#,d#,r#,a,d!,r!,w,q,q!,?]'
                : '[#,d#,r#,a,d!,r!,w,q,q!,?]';
            $action = $this->output->ask("Enter action $actions");

            switch ($action) {
                case '?':
                    // Display help.
                    $this->displayHelp();
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
                    // Remove all added elements and mark the initial ones for deletion.
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
                    // Add a new element to the list.
                    $elements = $this->addToList($elements);
                    break;
                default:
                    // Edit, remove or restore an element
                    list($action, $elementNumber) = $this->parseElementAction($action, $elements);
                    if (!$action) {
                        $this->error('Invalid action or element');
                        break;
                    }
                    switch ($action) {
                        case 'e':
                            $elements = $this->editElement($elements, $elementNumber);
                            break;
                        case 's':
                            if ($this->field->sortable) {
                                $elements = $this->sortElement($elements, $elementNumber);
                            } else {
                                $this->error('Invalid action (the list is not sortable)');
                            }
                            break;
                        case 'd':
                            $elements = $this->removeElement($elements, $elementNumber);
                            break;
                        case 'r':
                            $elements = $this->restoreElement($elements, $elementNumber);
                            break;
                        default:
                            $this->error('Invalid action');
                            break;
                    }
            }
        } while (!$exit);

        $layer->finish();
        return array_values(array_filter(array_column($elements, 1)));
    }

    /**
     * Display information about available actions and their keys.
     *
     * @return void
     */
    protected function displayHelp(): void
    {
        $lines = [
            ' # - edit element #',
            's# - set position of element #',
            'd# - delete element #',
            'r# - restore element # to its original value',
            ' a - add an element, also [c,n]',
            'd! - delete all elements',
            'r! - restore all elements to their original state',
            ' w - save changes and quit, also [wq]',
            ' q - quit (asks for confirmation if there are changes)',
            'q! - quit without saving',
            ' ? - print help',
        ];
        if (!$this->field->sortable) {
            unset($lines[1]);
        }
        $this->context->enqueue(fn () => $this->output->text($lines));
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
                $old !== null && $new === null => '<removed>%1$s</>',
                $old === null && $new !== null => '<added>%2$s</>',
                $old !== $new => '<removed>%1$s</> <added>%2$s</>',
                default => throw new \UnexpectedValueException('This should not happen'),
            };
            $rows[] = [
                $i + 1,
                sprintf($format, $this->field->formatValue($old), $this->field->formatValue($new))
            ];
        }
        $this->output->table(['#', 'Values'], $rows);
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
     * Add a new element.
     *
     * @param array<array{string|null,string|null}> $list
     * @return array<array{string|null,string|null}> Updated list
     */
    protected function addToList(array $list): array
    {
        $newValue = $this->field->field->askForValue($this->context, null);
        if ($newValue) {
            if (!in_array($newValue, array_column($list, 1))) {
                if (in_array($newValue, array_column($list, 0))) {
                    $n = array_search($newValue, array_column($list, 0));
                    $this->warning("Record $newValue was already selected, but marked for deletion");
                    $list[$n] = [$newValue, $newValue];
                } else {
                    $list[] = [null, $newValue];
                }
            } else {
                $this->warning("Record $newValue is already selected");
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
     * @return array{string|null,int|null} Next action and number of the selected element.
     */
    protected function parseElementAction(?string $action, array $list): array
    {
        if ($action === null) {
            return [null, null];
        }
        if (ctype_digit($action)) {
            $elementNumber = $action;
            $action = 'e';
        } else {
            $elementNumber = substr($action, 1);
            $action = substr($action, 0, 1);
        }
        return !ctype_digit($elementNumber) || (int) $elementNumber < 1 || (int) $elementNumber > sizeof($list)
            ? [null, null]
            : [$action, (int) $elementNumber - 1];
    }

    /**
     * Edit an element.
     *
     * @param array<array{string|null,string|null}> $list
     * @param int $elementNumber
     * @return array<array{string|null,string|null}> Updated list
     */
    protected function editElement(array $list, int $elementNumber): array
    {
        $newValue = $this->field->field->askForValue($this->context, $list[$elementNumber][1]);
        if (!$newValue) {
            $this->warning('Nothing was selected, selection was dismissed');
        } else {
            $list[$elementNumber][1] = $newValue;
        }
        return $list;
    }

    /**
     * Set a new position for an element.
     *
     * @param array<array{string|null,string|null}> $list
     * @param int $elementNumber
     * @return array<array{string|null,string|null}> Updated list
     */
    protected function sortElement(array $list, int $elementNumber): array
    {
        // Ask for the new element position.
        // Ensure that the wished position is in range.
        $newPosition = (int) $this->context->output->ask(
            'New position',
            (string) ($elementNumber + 1),
            fn ($answer) => (new IntegerValidator(1, sizeof($list)))->validate($answer),
        ) - 1;
        // Retain the selected element.
        $element = $list[$elementNumber];
        // Get elements except the selected one.
        $list = [
            ...array_slice($list, 0, $elementNumber),
            ...array_slice($list, $elementNumber + 1)
        ];
        // Insert the selected element on the wished position.
        $list = [
            ...array_slice($list, 0, $newPosition),
            $element,
            ...array_slice($list, $newPosition)
        ];
        return $list;
    }

    /**
     * Restore an element.
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
     * Remove a element.
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
