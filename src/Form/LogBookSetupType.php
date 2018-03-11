<?php

namespace App\Form;

use App\Entity\OsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LogBookSetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('nameShown',TextType::class,array(
                'required'=> false,
                'attr' => array('style' => 'width:auto%;')))
            ->add('disabled')
//            ->add('os')
            ->add('os', ChoiceType::class , $this->buildOsFormType())
            ->add('checkUpTime')
            ->add('owner')
            ->add('moderators')
            ->add('isPrivate')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
            //'data_class' => LogBookSetup::class,
        ]);
    }

    /**
     * @return array
     */
    protected function buildOsFormType(){
        return  array(
            'required' => false,
            'choices' => OsType::getAvailableTypes(),
            'preferred_choices' => OsType::getPreferredTypes(),
            //'choices_as_values' => true,
            'choice_label' => function ($choice) {
                return OsType::getTypeName($choice);
            },
        );
    }
}
