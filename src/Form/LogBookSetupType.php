<?php

namespace App\Form;

use App\Entity\LogBookSetup;
use App\Model\OsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LogBookSetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('nameShown')
            ->add('disabled')
//            ->add('os')
            ->add('os', ChoiceType::class , $this->buildFormType())
            ->add('checkUpTime')
            ->add('owner')
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
    protected function buildFormType(){
        //      add('type', ChoiceType::class , $this->buildFormType())->
        return  array(
            'required' => true,
            'choices' => OsType::getAvailableTypes(),
//            'choices_as_values' => true,
            'choice_label' => function($choice) {
                return OsType::getTypeName($choice);
            },
        );
    }
}
