<?php

namespace SRWieZ\GrpcProtoset;

use Google\Protobuf\Internal\DescriptorProto;
use Google\Protobuf\Internal\EnumDescriptorProto;
use Google\Protobuf\Internal\EnumValueDescriptorProto;
use Google\Protobuf\Internal\FieldDescriptorProto;
use Google\Protobuf\Internal\FileDescriptorProto;
use Google\Protobuf\Internal\FileDescriptorSet;
use Google\Protobuf\Internal\MethodDescriptorProto;
use Google\Protobuf\Internal\OneofDescriptorProto;
use Google\Protobuf\Internal\ServiceDescriptorProto;
use SRWieZ\GrpcProtoset\Exceptions\Exception;

class ProtosetConverter
{
    protected string $outputDir = './proto';

    public function setOutputDir(string $outputDir): self
    {
        $this->outputDir = $outputDir;

        return $this;
    }

    public function convertProtoset(string $protosetPath): void
    {
        $fileDescriptorSetBytes = file_get_contents($protosetPath);

        if ($fileDescriptorSetBytes === false) {
            throw new Exception('Failed to read protoset file');
        }

        $fileDescriptorSet = new FileDescriptorSet;
        $fileDescriptorSet->mergeFromString($fileDescriptorSetBytes);

        if (! is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }

        foreach ($fileDescriptorSet->getFile() as $fd) {
            /** @var FileDescriptorProto $fd */
            $name = $fd->getName();
            $filePath = $this->outputDir.DIRECTORY_SEPARATOR.$name;

            $dir = dirname($filePath);
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $file = fopen($filePath, 'w');

            if ($file === false) {
                throw new Exception("Failed to open file for writing: $filePath");
            }

            fwrite($file, "syntax = \"{$fd->getSyntax()}\";\n\n");
            fwrite($file, "package {$fd->getPackage()};\n\n");

            $options = $fd->getOptions();
            if ($options && $options->hasGoPackage()) {
                fwrite($file, "option go_package = \"{$options->getGoPackage()}\";\n\n");
            }

            foreach ($fd->getDependency() as $dep) {
                /** @var string $dep */
                fwrite($file, "import \"{$dep}\";\n");
            }
            if (count($fd->getDependency()) > 0) {
                fwrite($file, "\n");
            }

            foreach ($fd->getService() as $service) {
                /** @var ServiceDescriptorProto $service */
                $this->renderService($file, $service);
            }

            foreach ($fd->getMessageType() as $msg) {
                /** @var DescriptorProto $msg */
                $this->renderDescriptorProto($file, $msg);
            }

            foreach ($fd->getEnumType() as $enum) {
                /** @var EnumDescriptorProto $enum */
                fwrite($file, "enum {$enum->getName()} {\n");
                foreach ($enum->getValue() as $value) {
                    /** @var EnumValueDescriptorProto $value */
                    fwrite($file, "\t{$value->getName()} = {$value->getNumber()};\n");
                }
                fwrite($file, "}\n\n");
            }

            fclose($file);
        }
    }

    /**
     * @param  resource  $file
     *
     * @throws \Exception
     */
    private function renderDescriptorProto($file, DescriptorProto $msg): void
    {
        fwrite($file, "message {$msg->getName()} {\n");

        foreach ($msg->getField() as $field) {
            /** @var FieldDescriptorProto $field */
            if (empty($field->getOneofIndex())) {
                $this->writeField($file, $field);
            }
        }

        foreach ($msg->getOneofDecl() as $oneOfIndex => $oneof) {
            /** @var OneofDescriptorProto $oneof */
            $writeOneOf = array_reduce(iterator_to_array($msg->getField()),
                function ($carry, $field) use ($oneOfIndex) {
                    /** @var FieldDescriptorProto $field */
                    return $carry || ($field->getOneofIndex() !== 0 && $field->getOneofIndex() === $oneOfIndex);
                }, false);

            if (! $writeOneOf) {
                continue;
            }

            fwrite($file, "\toneof {$oneof->getName()} {\n");
            foreach ($msg->getField() as $field) {
                /** @var FieldDescriptorProto $field */
                if ($field->getOneofIndex() !== 0 && $field->getOneofIndex() === $oneOfIndex) {
                    $this->writeField($file, $field, true);
                }
            }
            fwrite($file, "\t}\n");
        }

        foreach ($msg->getNestedType() as $nestedMsg) {
            /** @var DescriptorProto $nestedMsg */
            $this->renderDescriptorProto($file, $nestedMsg);
        }

        foreach ($msg->getEnumType() as $enum) {
            /** @var EnumDescriptorProto $enum */
            fwrite($file, "\tenum {$enum->getName()} {\n");
            foreach ($enum->getValue() as $value) {
                /** @var EnumValueDescriptorProto $value */
                fwrite($file, "\t\t{$value->getName()} = {$value->getNumber()};\n");
            }
            fwrite($file, "\t}\n");
        }

        fwrite($file, "}\n\n");
    }

    /**
     * @param  resource  $file
     *
     * @throws \Exception
     */
    private function writeField($file, FieldDescriptorProto $field, bool $inOneof = false): void
    {
        $indent = $inOneof ? "\t\t" : "\t";

        if (! $inOneof && $field->getLabel() !== 0) {
            fwrite($file, $indent.$this->fmtFieldLabel($field->getLabel()).' ');
        }

        $fieldStr = sprintf(
            '%s%s %s = %d',
            $indent,
            $this->fmtFieldType($field->getType(), $field->getTypeName()),
            $field->getName(),
            $field->getNumber()
        );

        if ($field->getJsonName()) {
            $fieldStr .= " [json_name=\"{$field->getJsonName()}\"]";
        }

        fwrite($file, $fieldStr.";\n");
    }

    private function fmtFieldLabel(int $label): string
    {
        return match ($label) {
            1 => 'optional',
            2 => 'required',
            3 => 'repeated',
            default => throw new \Exception('Invalid field label')
        };
    }

    private function fmtFieldType(int $type, ?string $typeName): string
    {
        return match ($type) {
            1 => 'double',
            2 => 'float',
            3 => 'int64',
            4 => 'uint64',
            5 => 'int32',
            6 => 'fixed64',
            7 => 'fixed32',
            8 => 'bool',
            9 => 'string',
            10 => 'group',
            11 => $typeName ?? 'message', // message
            12 => 'bytes',
            13 => 'uint32',
            14 => $typeName ?? 'enum', // enum
            15 => 'sfixed32',
            16 => 'sfixed64',
            17 => 'sint32',
            18 => 'sint64',
            default => throw new \Exception('Invalid field type')
        };
    }

    /**
     * @param  resource  $file
     */
    private function renderService($file, ServiceDescriptorProto $service): void
    {
        fwrite($file, "service {$service->getName()} {\n");
        foreach ($service->getMethod() as $method) {
            /** @var MethodDescriptorProto $method */
            $clientStreaming = $method->getClientStreaming() ? 'stream ' : '';
            $serverStreaming = $method->getServerStreaming() ? 'stream ' : '';
            fwrite($file,
                "\trpc {$method->getName()}({$clientStreaming}{$method->getInputType()}) returns ({$serverStreaming}{$method->getOutputType()});\n");
        }
        fwrite($file, "}\n\n");
    }
}
