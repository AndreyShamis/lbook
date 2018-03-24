<?php

namespace App\Form;

use App\Entity\LogBookUser;
use App\Model\OsType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LogBookSetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('nameShown',TextType::class,array(
                'required'=> false,
                'attr' => array('style' => 'width:auto%;')))
            ->add('disabled')
//            ->add('os')
            ->add('os', ChoiceType::class , $this->buildOsFormType())
            ->add('checkUpTime');
        if (array_key_exists('user', $options) && $options['user'] !== null) {
            $builder->add('owner', EntityType::class, array(
                'class' => LogBookUser::class,
                'data' => $options['user'],
            ));
        }

        $builder
            //->add('owner')
            ->add('moderators')
            ->add('isPrivate')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
//        $resolver->setRequired(array(
//            'user'
//        ));
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
            //'data_class' => LogBookSetup::class,
            'user' => null,
        ]);
    }

    /**
     * @return array
     */
    protected function buildOsFormType(): array
    {
        return  array(
            'required' => false,
            'choices' => OsType::getAvailableTypes(),
            'preferred_choices' => OsType::getPreferredTypes(),
            //'choices_as_values' => true,
            'choice_label' => function($choice) {
                return OsType::getTypeName($choice);
            },
        );
    }
}
