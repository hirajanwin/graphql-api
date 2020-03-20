<?php
namespace Leoloso\GraphQLByPoPWPPlugin\Blocks;

use Leoloso\GraphQLByPoPWPPlugin\Blocks\BlockConstants;
use PoP\ComponentModel\Facades\Registries\TypeRegistryFacade;
use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;

trait WithTypeFieldControlBlockTrait
{
    /**
     * Convert the typeFields from the format saved in the post: "typeNamespacedName.fieldName",
     * to the one suitable for printing on the page, to show the user: "typeName/fieldName"
     *
     * @param array $typeFields
     * @return array
     */
    public function getTypeFieldsForPrint(array $typeFields): array
	{
        $instanceManager = InstanceManagerFacade::getInstance();
        $typeRegistry = TypeRegistryFacade::getInstance();
        $typeResolverClasses = $typeRegistry->getTypeResolverClasses();
        // For each class, obtain its namespacedTypeName
        $namespacedTypeNameNames = [];
        foreach ($typeResolverClasses as $typeResolverClass) {
            $typeResolver = $instanceManager->getInstance($typeResolverClass);
            $typeResolverNamespacedName = $typeResolver->getNamespacedTypeName();
            $namespacedTypeNameNames[$typeResolverNamespacedName] = $typeResolver->getTypeName();
        }
        return array_map(
            function($selectedField) use($namespacedTypeNameNames) {
                // The field is composed by the type namespaced name, and the field name, separated by "."
                // Extract these values
                $entry = explode(BlockConstants::TYPE_FIELD_SEPARATOR_FOR_DB, $selectedField);
                $namespacedTypeName = $entry[0];
                $field = $entry[1];
                $typeName = $namespacedTypeNameNames[$namespacedTypeName] ?? $namespacedTypeName;
                return $typeName.'/'.$field;
            },
            $typeFields
        );
    }
}