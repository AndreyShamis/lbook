<?php

namespace App\Form;

use App\Entity\LogBookUserSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookUserSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('cycleShowTestIdShow')
            ->add('cycleShowTestTimeStartShow')
            ->add('cycleShowTestTimeEndShow')
            ->add('cycleShowTestTimeRatioShow')
            ->add('cycleShowTestTimeStartFormat')
            ->add('cycleShowTestTimeEndFormat')
            ->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LogBookUserSettings::class,
        ]);
    }
}
