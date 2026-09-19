<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class CompletionGenerator
 *
 * Renders bash and zsh shell-completion scripts from a list of registered
 * commands. Reuses the same CommandDefinition metadata that HasDefinition
 * commands already expose for --help rendering — commands that don't
 * implement HasDefinition still get command-name completion, just no
 * per-command option completion.
 *
 * @package EzPhp\Console
 */
final class CompletionGenerator
{
    /**
     * CompletionGenerator Constructor
     *
     * @param list<CommandInterface> $commands
     */
    public function __construct(
        private readonly array $commands,
    ) {
    }

    /**
     * Render a bash completion script.
     *
     * @param string $programName CLI entry point name (e.g. 'ez').
     *
     * @return string
     */
    public function bash(string $programName = 'ez'): string
    {
        $functionName = "_{$programName}_complete";
        $names = array_map(fn (CommandInterface $c): string => $c->getName(), $this->commands);
        $commandList = implode(' ', $names);

        $cases = '';

        foreach ($this->commands as $command) {
            $options = $this->optionFlags($command);

            if ($options === []) {
                continue;
            }

            $cases .= '        ' . $command->getName() . ")\n";
            $cases .= '            COMPREPLY=($(compgen -W "' . implode(' ', $options) . "\" -- \"\$cur\"))\n";
            $cases .= "            ;;\n";
        }

        return <<<BASH
            # bash completion for {$programName}
            # Install: source this file, or place it in /etc/bash_completion.d/
            {$functionName}() {
                local cur prev
                cur="\${COMP_WORDS[COMP_CWORD]}"
                prev="\${COMP_WORDS[1]}"

                if [ "\${COMP_CWORD}" -eq 1 ]; then
                    COMPREPLY=(\$(compgen -W "{$commandList}" -- "\$cur"))
                    return 0
                fi

                case "\${prev}" in
            {$cases}        *)
                        COMPREPLY=()
                        ;;
                esac
            }
            complete -F {$functionName} {$programName}

            BASH;
    }

    /**
     * Render a zsh completion script.
     *
     * @param string $programName CLI entry point name (e.g. 'ez').
     *
     * @return string
     */
    public function zsh(string $programName = 'ez'): string
    {
        $functionName = "_{$programName}";
        $entries = '';

        foreach ($this->commands as $command) {
            $entries .= "        '" . $command->getName() . ':' . $this->escapeZsh($command->getDescription()) . "'\n";
        }

        $cases = '';

        foreach ($this->commands as $command) {
            $options = $this->optionFlags($command);

            if ($options === []) {
                continue;
            }

            $values = implode(' ', array_map(fn (string $o): string => "'{$o}'", $options));
            $cases .= '        ' . $command->getName() . ")\n";
            $cases .= "            _values 'options' {$values}\n";
            $cases .= "            ;;\n";
        }

        return <<<ZSH
            #compdef {$programName}
            # zsh completion for {$programName}
            # Install: place in a directory on \$fpath, named _{$programName}

            {$functionName}() {
                local -a commands
                commands=(
            {$entries}    )

                if (( CURRENT == 2 )); then
                    _describe 'command' commands
                    return
                fi

                case \${words[2]} in
            {$cases}        *)
                        ;;
                esac
            }

            {$functionName} "\$@"

            ZSH;
    }

    /**
     * @param CommandInterface $command
     *
     * @return list<string>
     */
    private function optionFlags(CommandInterface $command): array
    {
        if (!$command instanceof HasDefinition) {
            return [];
        }

        return array_map(
            fn (OptionDefinition $o): string => '--' . $o->name,
            $command->getDefinition()->getOptions(),
        );
    }

    /**
     * @param string $text
     *
     * @return string
     */
    private function escapeZsh(string $text): string
    {
        return str_replace("'", "'\\''", $text);
    }
}
