<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Aleste\Bundle\AdminLTEGeneratorBundle\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\GeneratorBundle\Generator\Generator as BaseGenerator;

/**
 * Generates a form class based on a Doctrine entity.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Hugo Hamon <hugo.hamon@sensio.com>
 */
class DoctrineFormGenerator extends BaseGenerator
{
    private $filesystem;
    private $className;
    private $classPath;

    /**
     * Constructor.
     *
     * @param Filesystem $filesystem A Filesystem instance
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
    }

    /**
     * Generates the entity form class.
     *
     * @param BundleInterface   $bundle         The bundle in which to create the class
     * @param string            $entity         The entity relative class name
     * @param ClassMetadataInfo $metadata       The entity metadata class
     * @param bool              $forceOverwrite If true, remove any existing form class before generating it again
     */
    public function generateFormFilter(BundleInterface $bundle, $entity, ClassMetadataInfo $metadata, $forceOverwrite = false)
    {
        $parts = explode('\\', $entity);
        $entityClass = array_pop($parts);

        $this->className = $entityClass.'FilterType';
        $dirPath = $bundle->getPath().'/Form';
        $this->classPath = $dirPath.'/'.str_replace('\\', '/', $entity).'FilterType.php';

        if (!$forceOverwrite && file_exists($this->classPath)) {
            throw new \RuntimeException(sprintf('Unable to generate the %s form class as it already exists under the %s file', $this->className, $this->classPath));
        }

        if (count($metadata->identifier) > 1) {
            throw new \RuntimeException('The form generator does not support entity classes with multiple primary keys.');
        }

        $parts = explode('\\', $entity);
        array_pop($parts);

        $this->renderFile('form/FormType.php.twig', $this->classPath, array(
            'fields' => $this->getFieldsFromMetadata($metadata),
            'fields_mapping' => $metadata->fieldMappings,
            'namespace' => $bundle->getNamespace(),
            'entity_namespace' => implode('\\', $parts),
            'entity_class' => $entityClass,
            'bundle' => $bundle->getName(),
            'form_class' => $this->className,
            'form_type_name' => strtolower(str_replace('\\', '_', $bundle->getNamespace()).($parts ? '_' : '').implode('_', $parts).'_'.substr($this->className, 0, -4)),

            // Add 'setDefaultOptions' method with deprecated type hint, if the new 'configureOptions' isn't available.
            // Required as long as Symfony 2.6 is supported.
            'configure_options_available' => method_exists('Symfony\Component\Form\AbstractType', 'configureOptions'),
            'get_name_required' => !method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix'),
        ));
    }    

}
