<?php

namespace App\Form;

use App\Entity\LogBookUserSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LogBookUserSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cycleShowTestIdShow')
            ->add('cycleShowTestTimeStartShow')
            ->add('cycleShowTestTimeEndShow')
            ->add('cycleShowTestTimeRatioShow')
            ->add('cycleShowTestMetaDataShow')
            ->add('cycleShowTestUptime')
            ->add('cycleShowTestTimeStartFormat', ChoiceType::class , $this->buildTimeShowFormat())
            ->add('cycleShowTestTimeEndFormat', ChoiceType::class , $this->buildTimeShowFormat())
//            ->add('user')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogBookUserSettings::class,
        ]);
    }

    private function buildTimeShowFormat(): array
    {
        return  array(
            'required' => false,
            'choices' => ['H:i:s', 'Y-m-d H:i:s', 'm-d H:i:s', 'd H:i:s'],
            'preferred_choices' => 'H:i:s',
            //'choices_as_values' => true,
            'choice_label' => function($choice) {
                return $choice;
            },
        );
    }
}
