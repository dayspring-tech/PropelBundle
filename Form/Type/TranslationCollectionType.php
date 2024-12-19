<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Bundle\PropelBundle\Form\Type;

use Propel\Bundle\PropelBundle\Form\EventListener\TranslationCollectionFormListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * form type for i18n-columns in propel.
 *
 * @author Patrick Kaufmann
 */
class TranslationCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!isset($options['entry_options']['data_class']) || null === $options['entry_options']['data_class']) {
            throw new MissingOptionsException('data_class must be set');
        }
        if (!isset($options['entry_options']['columns']) || null === $options['entry_options']['columns']) {
            throw new MissingOptionsException('columns must be set');
        }

        $listener = new TranslationCollectionFormListener($options['languages'], $options['entry_options']['data_class']);
        $builder->addEventSubscriber($listener);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['languages']);

        $resolver->setDefaults(['entry_type' => TranslationType::class]);
    }
}
