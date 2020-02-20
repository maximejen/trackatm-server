<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OperationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('place', null, [
                "required" => true
            ])
            ->add('cleaner', null, [
                "required" => true
            ])
            ->add('day', ChoiceType::class, [
                'choices' => [
                    "Monday" => "Monday",
                    "Tuesday" => "Tuesday",
                    "Wednesday" => "Wednesday",
                    "Thursday" => "Thursday",
                    "Friday" => "Friday",
                    "Saturday" => "Saturday",
                    "Sunday" => "Sunday"
                ],
                "required" => true
            ])
            ->add('template', null, [
                "required" => true
            ])
            ->add('numberMaxPerMonth')
        ;
    }/**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Operation'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_operation';
    }
}
