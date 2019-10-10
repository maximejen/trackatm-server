<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OperationTaskTemplateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'attr' => [
                    'class' => 'input'
                ]
            ])
            ->add('comment', null, [
                'attr' => [
                    'class' => 'input'
                ]
            ])
            ->add('imagesForced', null, [
                'attr' => [
                    'class' => 'checkbox'
                ]
            ])
            ->add('warningIfTrue', null, [
                'attr' => [
                    'class' => 'checkbox'
                ]
            ])
        ;
    }/**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\OperationTaskTemplate'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_operationtasktemplate';
    }


}
